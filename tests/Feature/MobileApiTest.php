<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

it('issues hashed expiring mobile tokens and never accepts a browser session alone', function () {
    $user = User::factory()->create(['password' => Hash::make('StrongPass123!')]);
    $this->actingAs($user)->getJson('/api/mobile/v1/dashboard')->assertUnauthorized();
    $login = $this->postJson('/api/mobile/v1/login', ['email' => $user->email, 'password' => 'StrongPass123!']);
    $login->assertOk()->assertJsonStructure(['token']);
    $token = $login->json('token');
    expect($user->tokens()->first()->token)->not->toBe($token)
        ->and($user->tokens()->first()->expires_at)->not->toBeNull();
    $this->withToken($token)->getJson('/api/mobile/v1/dashboard')->assertOk()->assertJsonPath('props.auth.user.id', $user->id);
    $this->postJson('/api/mobile/v1/logout')->assertOk();
    $this->getJson('/api/mobile/v1/dashboard')->assertUnauthorized();
});

it('rejects expired tokens and tokens without the mobile ability', function () {
    $user = User::factory()->create();
    $wrong = $user->createToken('wrong', ['other'])->plainTextToken;
    $this->withToken($wrong)->getJson('/api/mobile/v1/dashboard')->assertForbidden();
    $expired = $user->createToken('expired', ['mobile'], now()->subMinute())->plainTextToken;
    $this->withToken($expired)->getJson('/api/mobile/v1/dashboard')->assertUnauthorized();
});

it('uses server budgets and preserves financial validation and idempotency over the API', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $budget = Budget::create(['user_id' => $user->id, 'title' => 'Plan', 'currency' => 'MXN', 'income' => 100, 'details' => json_encode(['remaining' => 100])]);
    $token = $user->createToken('test', ['mobile'])->plainTextToken;
    $body = ['monto' => 25.5, 'descripcion' => 'Comida', 'currency' => 'MXN', 'request_id' => (string) Str::uuid()];
    $this->withToken($token)->postJson('/api/mobile/v1/quick-attack', $body)->assertOk();
    $this->postJson('/api/mobile/v1/quick-attack', $body)->assertOk();
    expect(Expense::count())->toBe(1)->and(json_decode($budget->fresh()->details, true)['remaining'])->toBe(74.5);
    $this->postJson('/api/mobile/v1/quick-attack', [...$body, 'monto' => 30])->assertUnprocessable()->assertJsonValidationErrors('request_id');
    $this->postJson('/api/mobile/v1/quick-attack', ['monto' => 10, 'descripcion' => 'Error', 'currency' => 'USD'])->assertUnprocessable()->assertJsonValidationErrors('currency');
    $foreign = Budget::create(['user_id' => User::factory()->create()->id, 'title' => 'Privado', 'income' => 100]);
    $this->getJson('/api/mobile/v1/presupuestos/exportar/'.$foreign->id)->assertNotFound();
});

it('forwards native credentials only to the configured https backend and keeps tokens out of page props', function () {
    config(['nativephp-internal.running' => true, 'mobile.backend_url' => 'https://finanzas-v3.onrender.com']);
    Http::fake([
        '*/api/mobile/v1/login' => Http::response(['token' => 'secret-test-token']),
        '*/api/mobile/v1/dashboard' => Http::response(['component' => 'Dashboard', 'props' => ['auth' => ['user' => ['id' => 999]], 'movementRequestId' => (string) Str::uuid(), 'flash' => []]]),
    ]);
    $this->post('/login', ['email' => 'test@example.test', 'password' => 'StrongPass123!'])->assertRedirect('/dashboard')->assertSessionHas('mobile_token', 'secret-test-token');
    $response = $this->get('/dashboard', ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/dashboard'))]);
    $response->assertOk()->assertJsonPath('props.auth.user.id', 999)->assertJsonPath('props.nativeRuntime', true);
    expect($response->getContent())->not->toContain('secret-test-token');
    expect(User::count())->toBe(0)->and(Expense::count())->toBe(0);
    Http::assertSent(fn ($request) => $request->url() === 'https://finanzas-v3.onrender.com/api/mobile/v1/dashboard' && $request->hasHeader('Authorization', 'Bearer secret-test-token'));
    $this->postJson('/api/mobile/v1/login', [])->assertNotFound();
});

it('keeps native drafts on validation and connection failures and never falls through to local financial writes', function () {
    config(['nativephp-internal.running' => true]);
    Http::fake(['*' => Http::response(['errors' => ['monto' => ['Importe inválido.']]], 422)]);
    $this->withSession(['mobile_token' => 'test'])->post('/quick-attack', ['monto' => 'bad'])->assertSessionHasErrors('monto');
    expect(Expense::count())->toBe(0);
    Http::fake(['*' => Http::failedConnection()]);
    $this->post('/quick-attack', ['monto' => 25])->assertSessionHasErrors('server');
    $this->get('/dashboard', ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/dashboard'))])->assertOk()->assertJsonPath('component', 'ConnectionError');
});

it('revokes all mobile tokens when a password changes', function () {
    $user = User::factory()->create(['password' => Hash::make('OldPass123!')]);
    $token = $user->createToken('phone', ['mobile'])->plainTextToken;
    $this->withToken($token)->putJson('/api/mobile/v1/password', ['current_password' => 'OldPass123!', 'password' => 'NewPass456!', 'password_confirmation' => 'NewPass456!'])->assertOk();
    expect($user->tokens()->count())->toBe(0)->and(Hash::check('NewPass456!', $user->fresh()->password))->toBeTrue();
    $this->getJson('/api/mobile/v1/dashboard')->assertUnauthorized();
});

it('registers native accounts on the server with the existing strong password and terms policy', function () {
    $this->postJson('/api/mobile/v1/register', ['name' => 'Persona', 'email' => 'mobile@example.test', 'password' => 'StrongPass123!', 'password_confirmation' => 'StrongPass123!', 'terms' => true])->assertOk()->assertJsonStructure(['token']);
    expect(User::where('email', 'mobile@example.test')->count())->toBe(1);
    $this->postJson('/api/mobile/v1/register', ['name' => 'Otra', 'email' => 'weak@example.test', 'password' => 'weak', 'password_confirmation' => 'weak', 'terms' => true])->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('revokes mobile tokens on password recovery and account deletion', function () {
    $user = User::factory()->create();
    $old = $user->createToken('lost phone', ['mobile'])->plainTextToken;
    $reset = Password::createToken($user);
    $this->postJson('/api/mobile/v1/reset-password', ['email' => $user->email, 'token' => $reset, 'password' => 'Recovered123!', 'password_confirmation' => 'Recovered123!'])->assertOk();
    $this->withToken($old)->getJson('/api/mobile/v1/dashboard')->assertUnauthorized();
    $new = $user->fresh()->createToken('phone', ['mobile'])->plainTextToken;
    $this->withToken($new)->deleteJson('/api/mobile/v1/profile', ['password' => 'Recovered123!'])->assertOk();
    expect($user->fresh())->toBeNull();
    $this->getJson('/api/mobile/v1/dashboard')->assertUnauthorized();
});

it('does not set cookies or cache the token response and rejects oversized bcrypt passwords', function () {
    $user = User::factory()->create(['password' => Hash::make('StrongPass123!')]);
    $response = $this->postJson('/api/mobile/v1/login', ['email' => $user->email, 'password' => 'StrongPass123!']);
    $response->assertOk();
    expect($response->headers->getCookies())->toBe([])->and($response->headers->get('Cache-Control'))->toContain('no-store');
    $password = str_repeat('á', 36).'A1!';
    $this->postJson('/api/mobile/v1/register', ['name' => 'Persona', 'email' => 'long@example.test', 'password' => $password, 'password_confirmation' => $password, 'terms' => true])->assertUnprocessable()->assertJsonValidationErrors('password');
});
