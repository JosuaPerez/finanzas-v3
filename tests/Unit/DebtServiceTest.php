<?php

/**
 * DebtServiceTest.php — Unit tests for App\Services\DebtService::getHPStats().
 *
 * Written in Pest PHP v3 syntax.
 * These tests instantiate DebtService directly (no HTTP layer, no database)
 * and feed it plain Debt model instances filled with attribute values.
 *
 * Test coverage:
 *   - HP stats for a loan with original_amount set
 *   - isCritical threshold: percent > 80 % triggers the critical flag
 *   - HP stats for a credit card with credit_limit + overdraft_percentage
 *   - Guard: balance exceeding computed maxHP raises maxHP to match balance
 */

use App\Models\Debt;
use App\Services\DebtService;

// ─── HELPER ───────────────────────────────────────────────────────────────────

/**
 * Build an unsaved Debt model instance with arbitrary attributes.
 * We use forceFill() to bypass the $fillable guard since we are testing
 * the service layer, not the model validation.
 */
function makeDebt(array $attributes): Debt
{
    return (new Debt())->forceFill($attributes);
}

// ─── LOAN WITH original_amount ────────────────────────────────────────────────

it('calculates HP stats correctly for a loan with original_amount', function () {
    $service = new DebtService();

    // Scenario: Player took a 1 000 000 DOP loan, 800 000 still pending.
    // maxHP must equal original_amount (not current balance).
    $debt = makeDebt([
        'type'            => 'loan',
        'balance'         => 800_000,
        'original_amount' => 1_000_000,
        'credit_limit'    => null,
    ]);

    $stats = $service->getHPStats($debt);

    expect($stats['current'])->toBe(800_000.0)
        ->and($stats['max'])->toBe(1_000_000.0)
        ->and($stats['percent'])->toBe(80.0)
        ->and($stats['isCritical'])->toBeFalse(); // 80 % is NOT > 80, so false
});

// ─── isCritical THRESHOLD ─────────────────────────────────────────────────────

it('marks a debt as critical when its HP percentage is strictly above 80 %', function () {
    $service = new DebtService();

    // 850 000 / 1 000 000 = 85 % → strictly above 80 → isCritical = true
    $debt = makeDebt([
        'type'            => 'loan',
        'balance'         => 850_000,
        'original_amount' => 1_000_000,
        'credit_limit'    => null,
    ]);

    $stats = $service->getHPStats($debt);

    expect($stats['percent'])->toBe(85.0)
        ->and($stats['isCritical'])->toBeTrue();
});

it('does NOT mark a debt as critical when its HP percentage is exactly 80 %', function () {
    $service = new DebtService();

    // 80 000 / 100 000 = 80.0 % → NOT > 80, so isCritical = false
    $debt = makeDebt([
        'type'            => 'loan',
        'balance'         => 80_000,
        'original_amount' => 100_000,
        'credit_limit'    => null,
    ]);

    $stats = $service->getHPStats($debt);

    expect($stats['percent'])->toBe(80.0)
        ->and($stats['isCritical'])->toBeFalse();
});

// ─── CREDIT CARD WITH LIMIT + OVERDRAFT ───────────────────────────────────────

it('calculates HP stats for a credit card using credit_limit + overdraft_percentage', function () {
    $service = new DebtService();

    // credit_limit = 100 000, overdraft_percentage = 10
    // maxHP = 100 000 * (1 + 10/100) = 110 000
    // balance = 55 000 → percent = (55 000 / 110 000) * 100 = 50 %
    $debt = makeDebt([
        'type'                => 'credit_card',
        'balance'             => 55_000,
        'credit_limit'        => 100_000,
        'overdraft_percentage' => 10,
        'original_amount'     => null,
    ]);

    $stats = $service->getHPStats($debt);

    // current and isCritical are exact; max uses a float delta because
    // 100_000 * 1.10 produces 110_000.00000000001 in IEEE 754 arithmetic.
    expect($stats['current'])->toBe(55_000.0)
        ->and($stats['max'])->toEqualWithDelta(110_000.0, 0.001)
        ->and(round($stats['percent'], 4))->toBe(50.0)
        ->and($stats['isCritical'])->toBeFalse();
});

it('correctly marks a credit card as critical when balance is high relative to limit', function () {
    $service = new DebtService();

    // credit_limit = 100 000, overdraft = 0 → maxHP = 100 000
    // balance = 95 000 → percent = 95 % → isCritical = true
    $debt = makeDebt([
        'type'                => 'credit_card',
        'balance'             => 95_000,
        'credit_limit'        => 100_000,
        'overdraft_percentage' => 0,
        'original_amount'     => null,
    ]);

    $stats = $service->getHPStats($debt);

    expect($stats['percent'])->toBe(95.0)
        ->and($stats['isCritical'])->toBeTrue();
});

// ─── BALANCE EXCEEDING maxHP GUARD ────────────────────────────────────────────

it('raises maxHP to match balance when accumulated interest pushes balance above the original limit', function () {
    $service = new DebtService();

    // Simulates a credit card where interest charges pushed the balance
    // beyond the original credit_limit (no overdraft buffer).
    // Without the guard, percent would exceed 100 and the bar would break.
    // With the guard: maxHP is raised to 120 000 and percent = 100 %.
    $debt = makeDebt([
        'type'                => 'credit_card',
        'balance'             => 120_000, // balance > credit_limit due to interest
        'credit_limit'        => 100_000,
        'overdraft_percentage' => 0,
        'original_amount'     => null,
    ]);

    $stats = $service->getHPStats($debt);

    expect($stats['max'])->toBe(120_000.0)           // guard raised maxHP
        ->and($stats['percent'])->toBe(100.0)          // clamped, not > 100
        ->and($stats['isCritical'])->toBeTrue();        // 100 % > 80 %
});

it('raises loan maxHP when balance exceeds original_amount due to interest', function () {
    $service = new DebtService();

    // A loan with original_amount = 500 000 but balance has grown to 520 000
    // (penalty interest). The guard must raise maxHP to 520 000.
    $debt = makeDebt([
        'type'            => 'loan',
        'balance'         => 520_000,
        'original_amount' => 500_000,
        'credit_limit'    => null,
    ]);

    $stats = $service->getHPStats($debt);

    expect($stats['max'])->toBe(520_000.0)  // guard raised maxHP to balance
        ->and($stats['percent'])->toBe(100.0) // percent clamped at 100
        ->and($stats['isCritical'])->toBeTrue();
});
