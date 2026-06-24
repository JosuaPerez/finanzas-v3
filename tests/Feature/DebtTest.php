<?php

/**
 * DebtTest.php — Feature tests for debt creation, combat (attack), and security.
 *
 * Written in Pest PHP v3 syntax. All tests share the RefreshDatabase trait and
 * Tests\TestCase base class via the global tests/Pest.php configuration.
 *
 * Test coverage:
 *   - Creating credit card debts
 *   - Creating loan debts
 *   - Successful attack: reduces boss HP in DB
 *   - Successful attack: deducts budget remaining + stores payment receipt
 *   - Security: 403 when User B attacks User A's debt
 *   - Security: 403 when User B deletes User A's debt
 *   - Edge case: payment exceeding balance clamps to zero (no negative HP)
 */

use App\Models\Budget;
use App\Models\Debt;
use App\Models\User;

// ─── DEBT CREATION ────────────────────────────────────────────────────────────

it('allows a user to register a credit card debt', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('debts.store'), [
            'type'                 => 'credit_card',
            'currency'             => 'USD',
            'name'                 => 'Visa Dólares',
            'balance'              => 500,
            'interest_rate'        => 60,
            'minimum_payment'      => 50,
            'credit_limit'         => 1000,
            'cutoff_date'          => 15,
            'payment_date'         => 5,
            'overdraft_percentage' => 10,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('debts', [
        'user_id'      => $user->id,
        'name'         => 'Visa Dólares',
        'type'         => 'credit_card',
        'currency'     => 'USD',
        'credit_limit' => 1000,
    ]);
});

it('allows a user to register a loan debt with its original amount', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('debts.store'), [
            'type'            => 'loan',
            'currency'        => 'DOP',
            'name'            => 'Préstamo Vehículo',
            'balance'         => 800000,
            'interest_rate'   => 14,
            'minimum_payment' => 15000,
            'original_amount' => 1000000,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('debts', [
        'name'            => 'Préstamo Vehículo',
        'type'            => 'loan',
        'original_amount' => 1000000,
    ]);
});

// ─── ATTACK (PAYMENT) LOGIC ───────────────────────────────────────────────────

it('reduces boss HP correctly in the database on a successful attack', function () {
    $user = User::factory()->create();

    Budget::create([
        'user_id'              => $user->id,
        'title'                => 'Quincena Test',
        'income'               => 60000,
        'fixed_expenses_total' => 25000,
        'details'              => json_encode(['remaining' => 35000]),
    ]);

    $debt = Debt::create([
        'user_id'         => $user->id,
        'name'            => 'El Gran Jefe',
        'balance'         => 20000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 12,
        'minimum_payment' => 2000,
    ]);

    $this->actingAs($user)
        ->post(route('debts.pay', $debt->id), ['amount' => 8000])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    // Boss HP reduced by exactly the attack amount
    $this->assertDatabaseHas('debts', [
        'id'      => $debt->id,
        'balance' => 12000, // 20000 − 8000
    ]);
});

it('deducts the budget remaining and stores a payment receipt on a successful attack', function () {
    $user = User::factory()->create();

    $budget = Budget::create([
        'user_id'              => $user->id,
        'title'                => 'Quincena Test',
        'income'               => 50000,
        'fixed_expenses_total' => 20000,
        'details'              => json_encode(['remaining' => 30000]),
    ]);

    $debt = Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Tarjeta Test',
        'balance'         => 10000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 0,
        'minimum_payment' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('debts.pay', $debt->id), ['amount' => 5000]);

    $budget->refresh();
    $details = is_string($budget->details)
        ? json_decode($budget->details, true)
        : (array) $budget->details;

    // Free capital deducted (json_decode returns int for whole numbers; cast to float for strict identity)
    expect((float) $details['remaining'])->toBe(25000.0);

    // Payment receipt written into budget details
    expect($details['debt_payments'])
        ->toHaveCount(1)
        ->and($details['debt_payments'][0]['name'])->toBe('Tarjeta Test');
    expect((float) $details['debt_payments'][0]['amount'])->toBe(5000.0);
});

it('clamps the debt balance to zero when the attack amount exceeds the remaining balance', function () {
    $user = User::factory()->create();

    $debt = Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Deuda Pequeña',
        'balance'         => 100,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 0,
        'minimum_payment' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('debts.pay', $debt->id), ['amount' => 9999])
        ->assertSessionHasNoErrors();

    // Balance must be 0, never negative — no negative HP in this game
    $this->assertDatabaseHas('debts', [
        'id'      => $debt->id,
        'balance' => 0,
    ]);
});

// ─── SECURITY (AUTHORIZATION) ─────────────────────────────────────────────────

it('returns 403 Forbidden when an unauthorized user tries to attack another user\'s debt', function () {
    $owner    = User::factory()->create(); // The debt's legitimate owner
    $attacker = User::factory()->create(); // The intruder — has no rights here

    $debt = Debt::create([
        'user_id'         => $owner->id,
        'name'            => 'Deuda Secreta',
        'balance'         => 5000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 10,
        'minimum_payment' => 500,
    ]);

    // The attacker tries to pay someone else's debt
    $this->actingAs($attacker)
        ->post(route('debts.pay', $debt->id), ['amount' => 1000])
        ->assertStatus(403); // Must be Forbidden — no exceptions

    // The debt balance must be completely unchanged
    $this->assertDatabaseHas('debts', [
        'id'      => $debt->id,
        'balance' => 5000,
    ]);
});

it('returns 403 Forbidden when an unauthorized user tries to delete another user\'s debt', function () {
    $owner    = User::factory()->create();
    $attacker = User::factory()->create();

    $debt = Debt::create([
        'user_id'         => $owner->id,
        'name'            => 'Deuda Protegida',
        'balance'         => 5000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 10,
        'minimum_payment' => 500,
    ]);

    $this->actingAs($attacker)
        ->delete(route('debts.destroy', $debt->id))
        ->assertStatus(403);

    // The debt must still be alive in the database
    $this->assertDatabaseHas('debts', ['id' => $debt->id]);
});

// ─── NEW COMBAT TESTS ─────────────────────────────────────────────────────────

/**
 * test_ataque_exitoso_reduce_hp_del_jefe
 *
 * A full combat round: the authenticated owner pays a debt (attacks the boss).
 * Verifies BOTH the HP reduction in the debts table AND the budget deduction
 * happen atomically in a single request.
 */
test('test_ataque_exitoso_reduce_hp_del_jefe', function () {
    $user = User::factory()->create();

    Budget::create([
        'user_id'              => $user->id,
        'title'                => 'Quincena Combate',
        'income'               => 100000,
        'fixed_expenses_total' => 30000,
        'details'              => json_encode(['remaining' => 70000]),
    ]);

    $debt = Debt::create([
        'user_id'         => $user->id,
        'name'            => 'El Gran Jefe RPG',
        'balance'         => 50000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 15,
        'minimum_payment' => 5000,
        'original_amount' => 60000,
    ]);

    // ── EXECUTE the attack (payment) ──────────────────────────────────────────
    $this->actingAs($user)
        ->post(route('debts.pay', $debt->id), ['amount' => 10000])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    // ── HP CHECK: boss balance reduced by the exact attack amount ─────────────
    $this->assertDatabaseHas('debts', [
        'id'      => $debt->id,
        'balance' => 40000, // 50 000 − 10 000
    ]);

    // ── BUDGET CHECK: free capital deducted + receipt stored ─────────────────
    $budget = Budget::where('user_id', $user->id)->latest()->first();
    $details = is_string($budget->details)
        ? json_decode($budget->details, true)
        : (array) $budget->details;

    expect((float) $details['remaining'])->toBe(60000.0); // 70 000 − 10 000 (cast: json_decode returns int)

    expect($details['debt_payments'])
        ->toHaveCount(1)
        ->and($details['debt_payments'][0]['name'])->toBe('El Gran Jefe RPG');
    expect((float) $details['debt_payments'][0]['amount'])->toBe(10000.0);
});

/**
 * test_otro_usuario_no_puede_pagar_deuda_ajena
 *
 * Security test: User B cannot attack User A's boss.
 * The endpoint must return 403 Forbidden and the debt balance must be
 * completely unchanged (no partial damage applied).
 */
test('test_otro_usuario_no_puede_pagar_deuda_ajena', function () {
    $owner   = User::factory()->create(); // Legítimo dueño de la deuda
    $intruder = User::factory()->create(); // El intruso — no tiene derechos aquí

    $debt = Debt::create([
        'user_id'         => $owner->id,
        'name'            => 'Jefe Ajeno',
        'balance'         => 15000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 12,
        'minimum_payment' => 1500,
    ]);

    // ── ATTACK from an unauthorized account ───────────────────────────────────
    $this->actingAs($intruder)
        ->post(route('debts.pay', $debt->id), ['amount' => 5000])
        ->assertStatus(403); // Must be Forbidden — no exceptions

    // ── INTEGRITY CHECK: boss HP must be completely untouched ─────────────────
    $this->assertDatabaseHas('debts', [
        'id'      => $debt->id,
        'balance' => 15000, // zero damage — the intruder failed
    ]);
});

// ─── AMMUNITION / BUDGET VALIDATION GUARD ─────────────────────────────────────

it('throws a municion validation error when a DOP attack exceeds available capital libre', function () {
    $user = User::factory()->create();

    Budget::create([
        'user_id'              => $user->id,
        'title'                => 'Presupuesto Bajo',
        'income'               => 30000,
        'fixed_expenses_total' => 25000,
        'details'              => json_encode(['remaining' => 5000]),
    ]);

    $debt = Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Deuda DOP Mayor',
        'balance'         => 20000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 10,
        'minimum_payment' => 1000,
    ]);

    $this->actingAs($user)
        ->post(route('debts.pay', $debt->id), ['amount' => 6000])
        ->assertInvalid(['municion' => 'Munición insuficiente (Capital Libre) para este ataque.']);
});

it('throws a municion validation error when a USD attack converted to DOP exceeds available capital libre', function () {
    $user = User::factory()->create();

    // Fallback rate is 60.50. Let's make remaining = 5000 DOP.
    // An attack of 100 USD = 6050 DOP > 5000 DOP.
    Budget::create([
        'user_id'              => $user->id,
        'title'                => 'Presupuesto USD',
        'income'               => 30000,
        'fixed_expenses_total' => 25000,
        'details'              => json_encode(['remaining' => 5000]),
    ]);

    $debt = Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Deuda USD Mayor',
        'balance'         => 500,
        'type'            => 'credit_card',
        'currency'        => 'USD',
        'interest_rate'   => 10,
        'minimum_payment' => 50,
    ]);

    $this->actingAs($user)
        ->post(route('debts.pay', $debt->id), ['amount' => 100])
        ->assertInvalid(['municion' => 'Munición insuficiente (Capital Libre) para este ataque.']);
});

it('allows a USD attack when the converted DOP cost is within available capital libre', function () {
    $user = User::factory()->create();

    // Fallback rate is 60.50. Let's make remaining = 10000 DOP.
    // An attack of 100 USD = 6050 DOP <= 10000 DOP.
    Budget::create([
        'user_id'              => $user->id,
        'title'                => 'Presupuesto Suficiente USD',
        'income'               => 50000,
        'fixed_expenses_total' => 20000,
        'details'              => json_encode(['remaining' => 10000]),
    ]);

    $debt = Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Deuda USD Menor',
        'balance'         => 500,
        'type'            => 'credit_card',
        'currency'        => 'USD',
        'interest_rate'   => 10,
        'minimum_payment' => 50,
    ]);

    $this->actingAs($user)
        ->post(route('debts.pay', $debt->id), ['amount' => 100])
        ->assertSessionHasNoErrors()
        ->assertRedirect();
});

it('bypasses the ammunition guard when the user has no budget or capital libre is zero', function () {
    $user = User::factory()->create();

    // No budget created for $user
    $debt = Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Deuda Sin Presupuesto',
        'balance'         => 5000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 10,
        'minimum_payment' => 500,
    ]);

    $this->actingAs($user)
        ->post(route('debts.pay', $debt->id), ['amount' => 1000])
        ->assertSessionHasNoErrors()
        ->assertRedirect();
});