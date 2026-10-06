<?php

use App\Exports\BudgetExport;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

it('prevents caching private financial pages and blocks access to another users export and goal', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $budget = Budget::create(['user_id' => $other->id, 'title' => 'Privado', 'income' => 100, 'details' => json_encode(['remaining' => 100])]);
    $goal = Goal::create(['user_id' => $other->id, 'name' => 'Privada', 'target_amount' => 100]);
    $response = $this->actingAs($user)->get('/dashboard')->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $this->get(route('budgets.export', $budget))->assertNotFound();
    $this->post(route('metas.add_funds', $goal), ['amount' => 10])->assertForbidden();
    $this->delete(route('metas.destroy', $goal))->assertForbidden();
    expect($goal->fresh())->not->toBeNull()->and((float) $goal->fresh()->current_amount)->toBe(0.0);
});

it('derives budget totals on the server and never accepts fabricated receipts or availability', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('budgets.store'), [
        'title' => 'Plan', 'income' => 100, 'fixed_expenses_total' => 1,
        'details' => ['fixed' => [['name' => 'Alquiler', 'amount' => 20]], 'remaining' => 9999,
            'expenses' => [['description' => 'Falso', 'amount' => 1]], 'debt_payments' => [['amount' => 1]]],
    ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();
    $budget = Budget::first();
    $details = json_decode($budget->details, true);
    expect((float) $budget->fixed_expenses_total)->toBe(20.0)
        ->and($details['remaining'])->toBe(80)
        ->and($details)->not->toHaveKey('debt_payments')
        ->and($details)->not->toHaveKey('expenses');
});

it('does not link a Google identity whose email ownership is unverified', function () {
    $user = User::factory()->create(['email' => 'existing@example.test']);
    $google = (new GoogleUser)->setRaw(['email_verified' => false])->map([
        'id' => 'unverified-id', 'email' => $user->email, 'name' => 'Unknown',
    ]);
    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($google);
    $this->get(route('auth.google.callback'))->assertForbidden();
    $this->assertGuest();
    expect($user->fresh()->google_id)->toBeNull();
});

it('does not reveal whether an email exists through password recovery', function () {
    Notification::fake();
    $user = User::factory()->create();
    $this->post(route('password.email'), ['email' => $user->email])->assertRedirect()->assertSessionHasNoErrors();
    $status = session('status');
    $this->post(route('password.email'), ['email' => 'missing@example.test'])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status', $status);
});

it('does not auto link Google to an account created with an unverified email', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $google = (new GoogleUser)->setRaw(['email_verified' => true])->map([
        'id' => 'verified-google-id', 'email' => $user->email, 'name' => 'Google User',
    ]);
    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($google);
    $this->get(route('auth.google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $this->assertGuest();
    expect($user->fresh()->google_id)->toBeNull();
});

it('requires strong passwords when changing credentials', function () {
    $user = User::factory()->create();
    $hash = $user->password;
    $this->actingAs($user)->put(route('password.update'), ['current_password' => 'password', 'password' => 'weak-password', 'password_confirmation' => 'weak-password'])->assertInvalid(['password']);
    expect($user->fresh()->password)->toBe($hash);
});

it('removes other database sessions reset tokens and financial data with account deletion', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'payload' => 'test', 'last_activity' => time()]);
    DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'test', 'created_at' => now()]);
    Budget::create(['user_id' => $user->id, 'title' => 'Eliminar', 'income' => 100]);
    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');
    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    $this->assertDatabaseMissing('budgets', ['user_id' => $user->id]);
    expect($other->fresh())->not->toBeNull();
});

it('requires recent matching OAuth confirmation to delete a passwordless account', function () {
    $user = User::factory()->create(['password' => null, 'google_id' => 'google-id']);
    $this->actingAs($user)->delete(route('profile.destroy'))->assertInvalid(['password']);
    $this->withSession(['google_confirmed_user_id' => $user->id, 'google_confirmed_at' => now()->subMinutes(6)->timestamp])
        ->delete(route('profile.destroy'))->assertInvalid(['password']);
    expect($user->fresh())->not->toBeNull();
    $this->withSession(['google_confirmed_user_id' => $user->id, 'google_confirmed_at' => now()->timestamp])
        ->delete(route('profile.destroy'))->assertRedirect('/');
    expect($user->fresh())->toBeNull();
});

it('exports user descriptions as text rather than executable spreadsheet formulas', function () {
    $user = User::factory()->create();
    $budget = Budget::create(['user_id' => $user->id, 'title' => 'Plan', 'income' => 100]);
    $export = new BudgetExport($budget);
    $sheet = (new Spreadsheet)->getActiveSheet();
    $export->bindValue($sheet->getCell('B2'), '=WEBSERVICE("https://example.test")');
    $export->bindValue($sheet->getCell('C2'), 25.50);
    expect($sheet->getCell('B2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($sheet->getCell('C2')->getDataType())->toBe(DataType::TYPE_NUMERIC);
});

it('keeps Laravel access while denying direct Supabase API roles access to financial data', function () {
    $this->assertSame('pgsql', DB::getDriverName());
    DB::statement('CREATE ROLE anon NOLOGIN');
    DB::statement('CREATE ROLE authenticated NOLOGIN');
    try {
        DB::statement('GRANT SELECT, INSERT ON TABLE public.users, public.expenses TO anon, authenticated');
        $migration = require database_path('migrations/2026_10_06_000002_restrict_supabase_public_access.php');
        $migration->up();
        $user = User::factory()->create();
        expect(User::find($user->id))->not->toBeNull();
        foreach (['anon', 'authenticated'] as $role) {
            $result = DB::selectOne("select has_table_privilege(?, 'public.users', 'SELECT') as allowed", [$role]);
            expect($result->allowed)->toBeFalse();
        }
        expect(DB::table('pg_class')->where('relname', 'expenses')->value('relrowsecurity'))->toBeTrue();
    } finally {
        // DCL is transactional; removing the test grants also allows role cleanup.
        DB::statement('DROP OWNED BY anon, authenticated');
        DB::statement('DROP ROLE anon, authenticated');
    }
})->skip(fn () => DB::getDriverName() !== 'pgsql', 'Supabase permissions require PostgreSQL');

it('does not reveal account existence through an invalid password reset token', function () {
    $user = User::factory()->create();
    $body = ['token' => 'invalid-token', 'password' => 'StrongPass123!', 'password_confirmation' => 'StrongPass123!'];
    $this->post(route('password.store'), [...$body, 'email' => $user->email])->assertSessionHasErrors('email');
    $error = session('errors')->first('email');
    $this->post(route('password.store'), [...$body, 'email' => 'missing@example.test'])->assertSessionHasErrors(['email' => $error]);
});
