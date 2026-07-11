<?php

/**
 * DebtPaymentCombatTest.php
 *
 * THE FIRST STRIKE — Critical tests for the Debt Payment & Combat sequence.
 *
 * What this file covers that existing tests do NOT:
 *
 *  SECTION A — Job Dispatch Verification (Queue::fake)
 *    A1. EvaluateAchievementsJob dispatched with 'debt_payment_made' on every payment.
 *    A2. Both 'debt_payment_made' AND 'debt_eliminated' dispatched on full payoff.
 *    A3. 'debt_eliminated' is NOT dispatched on a partial payment (1 job only).
 *
 *  SECTION B — CombatService XP Integration via HTTP endpoint
 *    B1. XP is awarded to the user when a payment defeats the active CampaignBoss.
 *    B2. No XP awarded when the payment only damages (not kills) the boss.
 *
 *  SECTION C — DebtService::applyPayment() Unit Tests (direct service calls)
 *    C1. Balance deducted by exact payment amount.
 *    C2. Returns false on partial payment.
 *    C3. Returns true on exact payoff.
 *    C4. Clamps to 0 on overpayment — no negative HP.
 *    C5. Budget remaining is reduced correctly.
 *    C6. Payment receipt appended to budget->details.
 *    C7. Null-safe: no exception when user has no budget.
 *    C8. Sequential payments accumulate receipts correctly.
 *
 * Pest PHP v3 syntax. RefreshDatabase + in-memory SQLite applied globally
 * via tests/Pest.php for all files under tests/Feature/.
 *
 * Run only this file:
 *   php artisan test --filter=DebtPaymentCombatTest
 */

use App\Jobs\EvaluateAchievementsJob;
use App\Models\Budget;
use App\Models\CampaignBoss;
use App\Models\Debt;
use App\Models\User;
use App\Services\DebtService;
use Illuminate\Support\Facades\Queue;

// ══════════════════════════════════════════════════════════════════════════════
// SECTION A — Job Dispatch Verification (Queue::fake)
// ══════════════════════════════════════════════════════════════════════════════

describe('EvaluateAchievementsJob dispatch', function () {

    /**
     * A1: Every successful payment must push a 'debt_payment_made' job.
     *
     * This is the async architecture test. If the Job is not dispatched the
     * achievement system is silently broken — users never unlock achievements.
     */
    it('dispatches EvaluateAchievementsJob with debt_payment_made event on every successful payment', function () {
        Queue::fake();

        $user = User::factory()->create();

        $debt = Debt::create([
            'user_id'         => $user->id,
            'name'            => 'Jefe Deuda Parcial',
            'balance'         => 20_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 10,
            'minimum_payment' => 2_000,
        ]);

        $this->actingAs($user)
            ->post(route('debts.pay', $debt->id), ['amount' => 5_000])
            ->assertSessionHasNoErrors();

        // The job must have been pushed to the queue — not executed synchronously.
        Queue::assertPushed(EvaluateAchievementsJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id
                && $job->event === 'debt_payment_made';
        });
    });

    /**
     * A2: When a payment eliminates the debt (balance → 0), BOTH events must
     * be dispatched: 'debt_payment_made' AND 'debt_eliminated'.
     *
     * The controller calls EvaluateAchievementsJob::dispatch() twice in this
     * scenario. Verifying both ensures neither dispatch was accidentally removed.
     */
    it('dispatches both debt_payment_made AND debt_eliminated jobs when a payment fully pays off a debt', function () {
        Queue::fake();

        $user = User::factory()->create();

        $debt = Debt::create([
            'user_id'         => $user->id,
            'name'            => 'Deuda Final',
            'balance'         => 1_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 5,
            'minimum_payment' => 500,
        ]);

        $this->actingAs($user)
            ->post(route('debts.pay', $debt->id), ['amount' => 1_000]) // exact payoff
            ->assertSessionHasNoErrors();

        // Both dispatches must be present — total of 2 jobs for this user.
        Queue::assertPushed(EvaluateAchievementsJob::class, 2);

        Queue::assertPushed(EvaluateAchievementsJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id
                && $job->event === 'debt_payment_made';
        });

        Queue::assertPushed(EvaluateAchievementsJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id
                && $job->event === 'debt_eliminated';
        });
    });

    /**
     * A3: Guard — no 'debt_eliminated' job is dispatched when the debt is only
     * partially paid. Only 1 job total should hit the queue.
     */
    it('does NOT dispatch the debt_eliminated job when the balance is only partially paid', function () {
        Queue::fake();

        $user = User::factory()->create();

        $debt = Debt::create([
            'user_id'         => $user->id,
            'name'            => 'Deuda Incompleta',
            'balance'         => 10_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 5,
            'minimum_payment' => 1_000,
        ]);

        $this->actingAs($user)
            ->post(route('debts.pay', $debt->id), ['amount' => 3_000])
            ->assertSessionHasNoErrors();

        // Exactly 1 job dispatched — no elimination event.
        Queue::assertPushed(EvaluateAchievementsJob::class, 1);

        Queue::assertNotPushed(EvaluateAchievementsJob::class, function ($job) {
            return $job->event === 'debt_eliminated';
        });
    });

});

// ══════════════════════════════════════════════════════════════════════════════
// SECTION B — CombatService XP Integration via HTTP endpoint
// ══════════════════════════════════════════════════════════════════════════════

describe('CombatService XP award via payment endpoint', function () {

    /**
     * B1: Full combat round through the HTTP layer.
     *
     * Making a payment that defeats the active CampaignBoss must:
     *   - Mark the boss as defeated in the DB (is_defeated = true, current_health = 0).
     *   - Award the boss's experience_reward XP to the user.
     *   - Persist the updated XP + level to the users table.
     *
     * This test validates the CombatService integration end-to-end
     * through the real HTTP request, not just the service in isolation.
     */
    it('awards boss XP to the user when a payment defeats the active campaign boss', function () {
        $user = User::factory()->create(['current_xp' => 0, 'level' => 1]);

        $debt = Debt::create([
            'user_id'         => $user->id,
            'name'            => 'Jefe Derrotable',
            'balance'         => 5_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 10,
            'minimum_payment' => 500,
        ]);

        // Boss with 50 HP remaining, worth 150 XP.
        // A 5000 DOP attack deals ceil(5000) = 5000 damage to the boss.
        // 5000 > 50 HP → boss is defeated → 150 XP awarded.
        // Level formula: floor(sqrt(150/100)) + 1 = floor(1.22) + 1 = 2.
        $boss = CampaignBoss::create([
            'user_id'           => $user->id,
            'name'              => 'El Gran Jefe de Combate',
            'max_health'        => 10_000,
            'current_health'    => 50,
            'experience_reward' => 150,
            'order'             => 1,
            'is_defeated'       => false,
        ]);

        $this->actingAs($user)
            ->post(route('debts.pay', $debt->id), ['amount' => 5_000])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        // Boss defeated
        $boss->refresh();
        expect($boss->is_defeated)->toBeTrue()
            ->and($boss->current_health)->toBe(0);

        // XP awarded and level updated
        $user->refresh();
        expect($user->current_xp)->toBe(150)
            ->and($user->level)->toBe(2);
    });

    /**
     * B2: Partial damage — no XP awarded, boss is still alive.
     *
     * Guards against accidental XP inflation if the defeat condition
     * check is ever accidentally removed or reversed.
     */
    it('does not award XP when the payment only partially damages the active boss', function () {
        $user = User::factory()->create(['current_xp' => 0, 'level' => 1]);

        $debt = Debt::create([
            'user_id'         => $user->id,
            'name'            => 'Jefe Resistente',
            'balance'         => 10_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 10,
            'minimum_payment' => 1_000,
        ]);

        // Boss has 10_000 HP — a 2000 DOP attack is nowhere near lethal.
        $boss = CampaignBoss::create([
            'user_id'           => $user->id,
            'name'              => 'Jefe Invencible',
            'max_health'        => 10_000,
            'current_health'    => 10_000,
            'experience_reward' => 500,
            'order'             => 1,
            'is_defeated'       => false,
        ]);

        $this->actingAs($user)
            ->post(route('debts.pay', $debt->id), ['amount' => 2_000])
            ->assertSessionHasNoErrors();

        // Boss survives
        $boss->refresh();
        expect($boss->is_defeated)->toBeFalse();

        // No XP granted
        $user->refresh();
        expect($user->current_xp)->toBe(0)
            ->and($user->level)->toBe(1);
    });

});

// ══════════════════════════════════════════════════════════════════════════════
// SECTION C — DebtService::applyPayment() Unit Tests (direct service calls)
// ══════════════════════════════════════════════════════════════════════════════

describe('DebtService::applyPayment()', function () {

    beforeEach(function () {
        $this->service = new DebtService();
        $this->user    = User::factory()->create();
    });

    /**
     * C1: Correct balance deduction — the core arithmetic contract.
     */
    it('correctly deducts the payment amount from the debt balance', function () {
        $debt = Debt::create([
            'user_id'         => $this->user->id,
            'name'            => 'Prueba Balance',
            'balance'         => 10_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 10,
            'minimum_payment' => 1_000,
        ]);

        $this->service->applyPayment($debt, 3_000);

        $debt->refresh();
        expect((float) $debt->balance)->toBe(7_000.0);
    });

    /**
     * C2: Returns false when the debt is partially paid.
     */
    it('returns false when the debt balance is not yet zero after payment', function () {
        $debt = Debt::create([
            'user_id'         => $this->user->id,
            'name'            => 'Deuda Parcial',
            'balance'         => 5_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 5,
            'minimum_payment' => 500,
        ]);

        $result = $this->service->applyPayment($debt, 2_000);

        expect($result)->toBeFalse();
    });

    /**
     * C3: Returns true when payment brings balance to exactly 0.
     */
    it('returns true when the payment exactly eliminates the debt balance', function () {
        $debt = Debt::create([
            'user_id'         => $this->user->id,
            'name'            => 'Deuda Exacta',
            'balance'         => 3_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 5,
            'minimum_payment' => 500,
        ]);

        $result = $this->service->applyPayment($debt, 3_000);

        expect($result)->toBeTrue();
        $debt->refresh();
        expect((float) $debt->balance)->toBe(0.0);
    });

    /**
     * C4: Overpayment clamps to 0 — the "no negative HP" rule.
     * Returns true because the debt IS fully cleared.
     */
    it('clamps balance to zero and returns true when the payment exceeds the remaining balance', function () {
        $debt = Debt::create([
            'user_id'         => $this->user->id,
            'name'            => 'Deuda Sobrepagada',
            'balance'         => 500,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 5,
            'minimum_payment' => 100,
        ]);

        $result = $this->service->applyPayment($debt, 99_999);

        expect($result)->toBeTrue();
        $debt->refresh();
        expect((float) $debt->balance)->toBe(0.0);
    });

    /**
     * C5: Budget remaining is deducted by the exact payment amount.
     */
    it('deducts the payment amount from the active budget remaining capital', function () {
        Budget::create([
            'user_id'              => $this->user->id,
            'title'                => 'Presupuesto Activo',
            'income'               => 50_000,
            'fixed_expenses_total' => 20_000,
            'details'              => json_encode(['remaining' => 30_000]),
        ]);

        $debt = Debt::create([
            'user_id'         => $this->user->id,
            'name'            => 'Deuda Presupuestada',
            'balance'         => 10_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 10,
            'minimum_payment' => 1_000,
        ]);

        $this->service->applyPayment($debt, 8_000);

        $budget  = Budget::where('user_id', $this->user->id)->latest()->first();
        $details = is_string($budget->details)
            ? json_decode($budget->details, true)
            : (array) $budget->details;

        expect((float) $details['remaining'])->toBe(22_000.0); // 30 000 − 8 000
    });

    /**
     * C6: Payment receipt is appended to budget->details['debt_payments'].
     */
    it('appends a payment receipt with the debt name and amount to budget details', function () {
        Budget::create([
            'user_id'              => $this->user->id,
            'title'                => 'Presupuesto Receipt',
            'income'               => 40_000,
            'fixed_expenses_total' => 15_000,
            'details'              => json_encode(['remaining' => 25_000]),
        ]);

        $debt = Debt::create([
            'user_id'         => $this->user->id,
            'name'            => 'El Jefe del Recibo',
            'balance'         => 8_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 10,
            'minimum_payment' => 800,
        ]);

        $this->service->applyPayment($debt, 4_000);

        $budget  = Budget::where('user_id', $this->user->id)->latest()->first();
        $details = is_string($budget->details)
            ? json_decode($budget->details, true)
            : (array) $budget->details;

        expect($details['debt_payments'])->toHaveCount(1);
        expect($details['debt_payments'][0]['name'])->toBe('El Jefe del Recibo');
        expect((float) $details['debt_payments'][0]['amount'])->toBe(4_000.0);
    });

    /**
     * C7: No budget scenario — applyPayment must NOT throw an exception.
     * The service must be null-safe: if no budget exists, it skips the
     * budget update silently and still deducts the debt balance.
     */
    it('does not throw an exception when no budget exists for the user and still deducts balance', function () {
        // No Budget created for this user
        $debt = Debt::create([
            'user_id'         => $this->user->id,
            'name'            => 'Deuda Sin Presupuesto',
            'balance'         => 5_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 10,
            'minimum_payment' => 500,
        ]);

        // Must not throw — this is the null-safety contract.
        expect(fn () => $this->service->applyPayment($debt, 2_000))->not->toThrow(\Throwable::class);

        // Debt balance was still deducted correctly.
        $debt->refresh();
        expect((float) $debt->balance)->toBe(3_000.0);
    });

    /**
     * C8: Multiple sequential payments append multiple receipts and correctly
     * accumulate budget deductions — the "two attacks on the same boss" scenario.
     */
    it('correctly accumulates multiple payment receipts in budget details across sequential payments', function () {
        Budget::create([
            'user_id'              => $this->user->id,
            'title'                => 'Presupuesto Multi-Ataque',
            'income'               => 100_000,
            'fixed_expenses_total' => 30_000,
            'details'              => json_encode(['remaining' => 70_000]),
        ]);

        $debt = Debt::create([
            'user_id'         => $this->user->id,
            'name'            => 'Jefe Multi-Round',
            'balance'         => 20_000,
            'type'            => 'loan',
            'currency'        => 'DOP',
            'interest_rate'   => 10,
            'minimum_payment' => 2_000,
        ]);

        $this->service->applyPayment($debt, 5_000); // Attack 1
        $debt->refresh();
        $this->service->applyPayment($debt, 3_000); // Attack 2

        $budget  = Budget::where('user_id', $this->user->id)->latest()->first();
        $details = is_string($budget->details)
            ? json_decode($budget->details, true)
            : (array) $budget->details;

        // remaining: 70 000 − 5 000 − 3 000 = 62 000
        expect((float) $details['remaining'])->toBe(62_000.0);

        // Two receipts, one per attack
        expect($details['debt_payments'])->toHaveCount(2);
        expect((float) $details['debt_payments'][0]['amount'])->toBe(5_000.0);
        expect((float) $details['debt_payments'][1]['amount'])->toBe(3_000.0);
    });

});
