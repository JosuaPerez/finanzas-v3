<?php

/**
 * RegistrationTest.php — Authentication & Registration Suite
 *
 * Covers both registration pathways of FinanzasRPG:
 *
 *  SECTION 1 — Standard Email/Password Registration (RegisteredUserController)
 *    1a. The /register page renders correctly (200 OK).
 *    1b. A new user can register with a valid payload (name, email, strong
 *        password + terms), is authenticated, and is redirected to /dashboard.
 *    1c. Registration is rejected when the 'terms' field is missing.
 *    1d. Registration is rejected when the password does not meet strength rules.
 *    1e. Registration is rejected when the email is already taken.
 *
 *  SECTION 2 — Google OAuth (GoogleAuthController via Laravel Socialite)
 *    2a. A brand-new Google user is created in the DB and authenticated.
 *    2b. An existing user whose email already exists gets their google_id linked
 *        and is authenticated (no duplicate user created).
 *    2c. A returning user with a known google_id is authenticated without any
 *        DB write (no duplicate user created).
 *    2d. Redirect to Google initiates correctly (Socialite redirect).
 *
 * Written in Pest PHP v3 syntax.
 * RefreshDatabase + in-memory SQLite applied globally via tests/Pest.php.
 *
 * Run only this file:
 *   php artisan test tests/Feature/Auth/RegistrationTest.php
 */

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

// ══════════════════════════════════════════════════════════════════════════════
// SECTION 1 — Standard Email/Password Registration
// ══════════════════════════════════════════════════════════════════════════════

describe('Standard registration', function () {

    /**
     * 1a. The registration page must render correctly.
     */
    it('renders the registration screen', function () {
        $this->get('/register')
            ->assertStatus(200);
    });

    /**
     * 1b. Happy path: valid payload → user created, authenticated, redirected.
     *
     * Our RegisteredUserController requires:
     *   - min-8, letters, mixedCase, numbers, symbols password
     *   - 'terms' accepted (custom Terms & Conditions checkbox)
     */
    it('creates a new user, authenticates them, and redirects to dashboard on valid registration', function () {
        $this->post('/register', [
            'name'                  => 'Comandante Test',
            'email'                 => 'commander@finanzasrpg.com',
            'password'              => 'Password123!@#',
            'password_confirmation' => 'Password123!@#',
            'terms'                 => true,
        ])->assertSessionHasNoErrors()
          ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'commander@finanzasrpg.com',
        ]);
    });

    /**
     * 1c. Missing 'terms' field → validation error, user NOT created.
     */
    it('rejects registration when the terms checkbox is not accepted', function () {
        $this->post('/register', [
            'name'                  => 'Sin Términos',
            'email'                 => 'noterms@finanzasrpg.com',
            'password'              => 'Password123!@#',
            'password_confirmation' => 'Password123!@#',
            // 'terms' intentionally omitted
        ])->assertSessionHasErrors('terms');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'noterms@finanzasrpg.com']);
    });

    /**
     * 1d. Weak password → validation error, user NOT created.
     *     Our rule requires: min-8, letters, mixedCase, numbers, symbols.
     */
    it('rejects registration when the password does not meet strength requirements', function () {
        $this->post('/register', [
            'name'                  => 'Password Débil',
            'email'                 => 'weakpass@finanzasrpg.com',
            'password'              => 'password',  // no uppercase, no number, no symbol
            'password_confirmation' => 'password',
            'terms'                 => true,
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'weakpass@finanzasrpg.com']);
    });

    /**
     * 1e. Duplicate email → validation error, no second user created.
     */
    it('rejects registration when the email address is already taken', function () {
        User::factory()->create(['email' => 'taken@finanzasrpg.com']);

        $this->post('/register', [
            'name'                  => 'Duplicado',
            'email'                 => 'taken@finanzasrpg.com',
            'password'              => 'Password123!@#',
            'password_confirmation' => 'Password123!@#',
            'terms'                 => true,
        ])->assertSessionHasErrors('email');

        // Only the factory user exists — no duplicate
        expect(User::where('email', 'taken@finanzasrpg.com')->count())->toBe(1);
    });

});

// ══════════════════════════════════════════════════════════════════════════════
// SECTION 2 — Google OAuth via Laravel Socialite
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Helper: build a fake SocialiteUser DTO with controlled field values.
 * Uses Mockery to satisfy the SocialiteUser interface without an HTTP call.
 */
function makeFakeGoogleUser(string $id, string $name, string $email): SocialiteUser
{
    $fake = Mockery::mock(SocialiteUser::class);
    $fake->allows('getId')->andReturn($id);
    $fake->allows('getName')->andReturn($name);
    $fake->allows('getEmail')->andReturn($email);
    return $fake;
}

describe('Google OAuth callback (Socialite)', function () {

    /**
     * 2a. Brand-new Google user — no matching google_id or email in DB.
     *     Expected: one new User row created, session authenticated, redirect to /dashboard.
     */
    it('creates a new user and authenticates them when no matching account exists', function () {
        $fakeGoogleUser = makeFakeGoogleUser('google-uid-001', 'Nuevo Comandante', 'nuevo@gmail.com');

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturnSelf();

        Socialite::shouldReceive('user')
            ->andReturn($fakeGoogleUser);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email'     => 'nuevo@gmail.com',
            'name'      => 'Nuevo Comandante',
            'google_id' => 'google-uid-001',
        ]);

        expect(User::where('email', 'nuevo@gmail.com')->count())->toBe(1);
    });

    /**
     * 2b. Email already registered (traditional account) — no google_id yet.
     *     Expected: existing user gets google_id attached, authenticated, no new user created.
     */
    it('links google_id to an existing email account and authenticates them without creating a duplicate user', function () {
        $existing = User::factory()->create([
            'email'     => 'existing@gmail.com',
            'google_id' => null,
        ]);

        $fakeGoogleUser = makeFakeGoogleUser('google-uid-002', $existing->name, 'existing@gmail.com');

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturnSelf();

        Socialite::shouldReceive('user')
            ->andReturn($fakeGoogleUser);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        // google_id attached to the EXISTING user — no new row created
        $existing->refresh();
        expect($existing->google_id)->toBe('google-uid-002');
        expect(User::where('email', 'existing@gmail.com')->count())->toBe(1);
    });

    /**
     * 2c. Returning Google user — google_id already in DB.
     *     Expected: user is logged in directly, no DB writes, no duplicate created.
     */
    it('authenticates a returning user who already has a linked google_id without modifying the database', function () {
        $returning = User::factory()->create([
            'email'     => 'returning@gmail.com',
            'google_id' => 'google-uid-003',
        ]);

        $xpBefore = $returning->fresh()->current_xp; // Snapshot from DB (default = 0, not null)

        $fakeGoogleUser = makeFakeGoogleUser('google-uid-003', $returning->name, 'returning@gmail.com');

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturnSelf();

        Socialite::shouldReceive('user')
            ->andReturn($fakeGoogleUser);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        // Authenticated as the correct user
        $this->assertAuthenticatedAs($returning);

        // No new rows — only the one factory user exists
        expect(User::where('email', 'returning@gmail.com')->count())->toBe(1);

        // XP and other fields untouched
        $returning->refresh();
        expect($returning->current_xp)->toBe($xpBefore);
    });

    /**
     * 2d. The /auth/google redirect route returns a redirect response
     *     (Socialite initiates the OAuth flow toward Google's consent screen).
     */
    it('redirects to Google consent screen when the OAuth redirect route is hit', function () {
        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturnSelf();

        Socialite::shouldReceive('redirect')
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth?fake=1'));

        $this->get(route('auth.google.redirect'))
            ->assertRedirect();
    });

});
