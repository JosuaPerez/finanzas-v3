<?php

use App\Exports\BudgetExport;
use App\Models\Budget;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Str;

function expenseBudget(User $user, float $remaining, string $currency = 'MXN'): Budget
{
    return Budget::create(['user_id' => $user->id, 'title' => 'Plan', 'currency' => $currency,
        'income' => 100, 'details' => json_encode(['remaining' => $remaining, 'fixed' => []])]);
}

it('deducts cents from only the latest owned budget and records an exportable receipt', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $old = expenseBudget($user, 100);
    $active = expenseBudget($user, 50.10);
    $other = expenseBudget(User::factory()->create(), 200);
    $this->actingAs($user)->post(route('quick-attack.store'), ['monto' => 0.20, 'descripcion' => 'Agua'])->assertSessionHasNoErrors();
    $details = json_decode($active->fresh()->details, true);
    expect($details['remaining'])->toBe(49.9)
        ->and(json_decode($old->fresh()->details, true)['remaining'])->toBe(100)
        ->and(json_decode($other->fresh()->details, true)['remaining'])->toBe(200)
        ->and(Expense::first()->budget_id)->toBe($active->id)
        ->and(Expense::first()->budget_deducted)->toBeTrue()
        ->and($details['expenses'][0]['amount'])->toBe(0.2)
        ->and((new BudgetExport($active->fresh()))->array())->toContain(['Gasto registrado', 'Agua', 0.2]);
});

it('does not duplicate the expense deduction or xp when a submission is retried', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $budget = expenseBudget($user, 100);
    $payload = ['monto' => 25.50, 'descripcion' => 'Transporte', 'request_id' => (string) Str::uuid()];
    $this->actingAs($user)->post(route('quick-attack.store'), $payload)->assertSessionHasNoErrors();
    $this->post(route('quick-attack.store'), $payload)->assertSessionHasNoErrors();
    expect(Expense::count())->toBe(1)
        ->and(json_decode($budget->fresh()->details, true)['remaining'])->toBe(74.5)
        ->and(json_decode($budget->fresh()->details, true)['expenses'])->toHaveCount(1)
        ->and($user->fresh()->current_xp)->toBe(15);
});

it('does not deduct a fixed expense twice and exports no additional cost', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $budget = expenseBudget($user, 50);
    $this->actingAs($user)->post(route('quick-attack.store'), [
        'monto' => 30, 'descripcion' => 'Alquiler', 'already_budgeted' => true,
    ])->assertSessionHasNoErrors();
    expect(json_decode($budget->fresh()->details, true)['remaining'])->toBe(50)
        ->and(Expense::first()->budget_deducted)->toBeFalse()
        ->and((new BudgetExport($budget->fresh()))->array())->toContain(['Gasto registrado', 'Alquiler (ya incluido en gastos fijos)', 0]);
});

it('preserves overspending as a negative available amount including from zero', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $budget = expenseBudget($user, 0);
    $this->actingAs($user)->post(route('quick-attack.store'), ['monto' => 1.25, 'descripcion' => 'Comida'])->assertSessionHasNoErrors();
    $this->post(route('quick-attack.store'), ['monto' => 2.50, 'descripcion' => 'Transporte'])->assertSessionHasNoErrors();
    expect(json_decode($budget->fresh()->details, true)['remaining'])->toBe(-3.75);
});

it('preserves expense receipts when a debt payment updates the same budget and never erases a negative balance', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $budget = expenseBudget($user, 100);
    $debt = Debt::create(['user_id' => $user->id, 'name' => 'Préstamo', 'currency' => 'MXN', 'balance' => 100]);
    $this->actingAs($user)->post(route('quick-attack.store'), ['monto' => 25, 'descripcion' => 'Comida'])->assertSessionHasNoErrors();
    $this->post(route('debts.pay', $debt), ['amount' => 10])->assertSessionHasNoErrors();
    $details = json_decode($budget->fresh()->details, true);
    expect($details['remaining'])->toBe(65)->and($details['expenses'])->toHaveCount(1)
        ->and($details['debt_payments'])->toHaveCount(1)->and(Expense::count())->toBe(1);

    $this->post(route('quick-attack.store'), ['monto' => 70, 'descripcion' => 'Compra'])->assertSessionHasNoErrors();
    $this->post(route('debts.pay', $debt), ['amount' => 1])->assertInvalid(['municion']);
    expect(json_decode($budget->fresh()->details, true)['remaining'])->toBe(-5)
        ->and((float) $debt->fresh()->balance)->toBe(90.0);
});

it('rejects currency mismatches unknown availability and fractional cents without storing or deducting', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $budget = expenseBudget($user, 100);
    $this->actingAs($user)->post(route('quick-attack.store'), ['monto' => 25, 'descripcion' => 'USD', 'currency' => 'USD'])->assertInvalid(['currency']);
    $this->post(route('quick-attack.store'), ['monto' => 0.001, 'descripcion' => 'Impreciso'])->assertInvalid(['monto']);
    expect(json_decode($budget->fresh()->details, true)['remaining'])->toBe(100);
    $budget->update(['details' => json_encode([])]);
    $this->post(route('quick-attack.store'), ['monto' => 25, 'descripcion' => 'Sin disponible'])->assertInvalid(['monto']);
    expect(Expense::count())->toBe(0)->and($user->fresh()->current_xp)->toBe(0);
});

it('keeps expenses without a budget independent and never charges them retroactively', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $this->actingAs($user)->post(route('quick-attack.store'), ['monto' => 25, 'descripcion' => 'Anterior'])->assertSessionHasNoErrors();
    expect(Expense::first()->budget_id)->toBeNull()->and(Expense::first()->budget_deducted)->toBeFalse();
    $budget = expenseBudget($user, 100);
    $this->post(route('quick-attack.store'), ['monto' => 10, 'descripcion' => 'Nuevo'])->assertSessionHasNoErrors();
    expect(json_decode($budget->fresh()->details, true)['remaining'])->toBe(90)
        ->and(Expense::first()->budget_id)->toBeNull();
});

it('preserves legacy expenses and balances when adding budget linkage', function () {
    $migration = require database_path('migrations/2026_10_06_000001_link_expenses_to_budgets.php');
    $migration->down();
    try {
        $user = User::factory()->create();
        $budget = expenseBudget($user, 100, 'DOP');
        $expense = Expense::create(['user_id' => $user->id, 'amount' => 25, 'description' => 'Histórico']);
    } finally {
        $migration->up();
    }
    expect((float) $expense->fresh()->amount)->toBe(25.0)
        ->and($expense->fresh()->currency)->toBe('DOP')
        ->and($expense->fresh()->budget_id)->toBeNull()
        ->and($expense->fresh()->budget_deducted)->toBeFalse()
        ->and(json_decode($budget->fresh()->details, true)['remaining'])->toBe(100);
});
