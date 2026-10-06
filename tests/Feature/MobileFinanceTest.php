<?php

use App\Models\Budget;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Goal;
use App\Models\User;
use App\Services\BpdExchangeRateService;
use Inertia\Testing\AssertableInertia as Assert;

it('persists regional preferences without relabelling or changing existing records', function () {
    $user = User::factory()->create();
    $budget = Budget::create(['user_id' => $user->id, 'title' => 'Anterior', 'income' => 1000, 'details' => json_encode(['remaining' => 750])]);
    $expense = Expense::create(['user_id' => $user->id, 'description' => 'Anterior', 'amount' => 50]);
    $debt = Debt::create(['user_id' => $user->id, 'name' => 'Anterior USD', 'currency' => 'USD', 'balance' => 20]);
    $goal = Goal::create(['user_id' => $user->id, 'name' => 'Anterior DOP', 'currency' => 'DOP', 'target_amount' => 100]);

    $this->actingAs($user)->patch(route('profile.financial-preferences'), [
        'preferred_currency' => 'MXN', 'number_locale' => 'es-MX',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->preferred_currency)->toBe('MXN')
        ->and($user->fresh()->number_locale)->toBe('es-MX')
        ->and($user->fresh()->financial_preferences_set_at)->not->toBeNull()
        ->and($budget->fresh()->currency)->toBe('DOP')
        ->and((float) $budget->fresh()->income)->toBe(1000.0)
        ->and($expense->fresh()->currency)->toBe('DOP')
        ->and($debt->fresh()->currency)->toBe('USD')
        ->and($goal->fresh()->currency)->toBe('DOP');
});

it('rejects unsupported preferences and requires authentication', function () {
    $this->patch(route('profile.financial-preferences'), ['preferred_currency' => 'MXN', 'number_locale' => 'es-MX'])->assertRedirect('/login');
    $user = User::factory()->create();
    $this->actingAs($user)->patch(route('profile.financial-preferences'), [
        'preferred_currency' => 'XYZ', 'number_locale' => 'invalid',
    ])->assertInvalid(['preferred_currency', 'number_locale']);
    expect($user->fresh()->preferred_currency)->toBe('DOP');
});

it('keeps each currency separate on the dashboard and scopes every record to its owner', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $other = User::factory()->create();
    foreach ([['MXN', 100], ['USD', 200], ['DOP', 300]] as [$currency, $amount]) {
        Debt::create(['user_id' => $user->id, 'name' => $currency, 'currency' => $currency, 'balance' => $amount]);
        Goal::create(['user_id' => $user->id, 'name' => $currency, 'currency' => $currency, 'target_amount' => $amount * 2, 'current_amount' => $amount]);
    }
    Debt::create(['user_id' => $other->id, 'name' => 'Ajena', 'currency' => 'MXN', 'balance' => 9999, 'payment_date' => 1]);
    $budget = Budget::create(['user_id' => $user->id, 'title' => 'Presupuesto DOP', 'currency' => 'DOP', 'income' => 1000, 'details' => json_encode(['remaining' => 0])]);
    Expense::create(['user_id' => $user->id, 'description' => 'Dólares', 'amount' => 10, 'currency' => 'USD']);

    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('totalDebts', 100)->has('debtTotals', 3)->has('goalTotals', 3)
        ->where('debtTotals.1.currency', 'USD')->where('debtTotals.1.amount', 200)
        ->where('goalTotals.1.saved', 200)->where('available.currency', 'DOP')
        ->where('available.amount', 0)->where('nextPayment', null)
        ->where('combatLog.0.currency', 'USD')->has('movementDebts', 3));
});

it('shows missing budget data as unknown and does not turn negative or zero availability into missing data', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('available', null)->where('nextPayment', null)->where('featuredGoal', null));
    $budget = Budget::create(['user_id' => $user->id, 'title' => 'Sin cálculo', 'income' => 100]);
    $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('available.amount', null));
    $budget->update(['details' => json_encode(['remaining' => -20])]);
    $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('available.amount', -20));
});

it('uses the configured recurring payment day and clamps it to the actual month length', function () {
    $this->travelTo(\Carbon\Carbon::parse('2027-02-15 12:00:00'));
    $user = User::factory()->create();
    Debt::create(['user_id' => $user->id, 'name' => 'Día 31', 'balance' => 500, 'payment_date' => 31, 'minimum_payment' => 25, 'currency' => 'EUR']);
    Debt::create(['user_id' => $user->id, 'name' => 'Sin día', 'balance' => 500]);
    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('nextPayment.date', '2027-02-28')->where('nextPayment.name', 'Día 31')
        ->where('nextPayment.currency', 'EUR')->where('nextPayment.amount', 25));
    $this->travelBack();
});

it('stores an expense once in its selected currency without changing budget or debt balances', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $budget = Budget::create(['user_id' => $user->id, 'title' => 'Plan', 'income' => 100, 'details' => json_encode(['remaining' => 100])]);
    $debt = Debt::create(['user_id' => $user->id, 'name' => 'Deuda', 'balance' => 100]);
    $this->actingAs($user)->post(route('quick-attack.store'), ['monto' => 25.50, 'descripcion' => 'Transporte', 'currency' => 'MXN'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('expenses', ['user_id' => $user->id, 'amount' => 25.50, 'currency' => 'MXN']);
    expect(Expense::count())->toBe(1)->and((float) $debt->fresh()->balance)->toBe(100.0)
        ->and(json_decode($budget->fresh()->details, true)['remaining'])->toBe(100)
        ->and($user->fresh()->current_xp)->toBe(15);
    $this->post(route('quick-attack.store'), ['monto' => -1, 'descripcion' => '', 'currency' => 'XYZ'])->assertInvalid(['monto', 'descripcion', 'currency']);
    expect(Expense::count())->toBe(1);
});

it('creates new budgets, debts and goals in a supported currency without converting older amounts', function () {
    $user = User::factory()->create(['preferred_currency' => 'EUR']);
    $this->actingAs($user)->post(route('budgets.store'), [
        'title' => 'Nuevo', 'income' => 1000, 'fixed_expenses_total' => 200, 'details' => ['remaining' => 800],
    ])->assertSessionHasNoErrors();
    $this->post(route('debts.store'), ['name' => 'Deuda', 'currency' => 'EUR', 'balance' => 500, 'interest_rate' => 0, 'minimum_payment' => 50, 'type' => 'loan'])->assertSessionHasNoErrors();
    $this->post(route('metas.store'), ['name' => 'Fondo', 'currency' => 'EUR', 'target_amount' => 1000])->assertSessionHasNoErrors();
    expect(Budget::first()->currency)->toBe('EUR')->and(Debt::first()->currency)->toBe('EUR')->and(Goal::first()->currency)->toBe('EUR');
});

it('deducts a same-currency payment without creating a duplicate expense or calling an exchange API', function () {
    $this->mock(BpdExchangeRateService::class)->shouldNotReceive('getUsdSellRate');
    $user = User::factory()->create();
    $budget = Budget::create(['user_id' => $user->id, 'title' => 'Plan EUR', 'currency' => 'EUR', 'income' => 200, 'details' => json_encode(['remaining' => 200])]);
    $debt = Debt::create(['user_id' => $user->id, 'name' => 'Deuda EUR', 'currency' => 'EUR', 'balance' => 100]);
    $this->actingAs($user)->post(route('debts.pay', $debt), ['amount' => 25.50])->assertSessionHasNoErrors();
    $details = (array) $budget->fresh()->details;
    if (is_string($budget->fresh()->details)) $details = json_decode($budget->fresh()->details, true);
    expect((float) $debt->fresh()->balance)->toBe(74.5)
        ->and((float) $details['remaining'])->toBe(174.5)
        ->and($details['debt_payments'][0]['currency'])->toBe('EUR')
        ->and(Expense::count())->toBe(0);
});

it('rejects unsupported cross-currency payments and payments on another users debt without mutating data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Budget::create(['user_id' => $user->id, 'title' => 'Plan MXN', 'currency' => 'MXN', 'income' => 200, 'details' => json_encode(['remaining' => 200])]);
    $debt = Debt::create(['user_id' => $user->id, 'name' => 'Deuda EUR', 'currency' => 'EUR', 'balance' => 100]);
    $foreign = Debt::create(['user_id' => $other->id, 'name' => 'Ajena', 'balance' => 100]);
    $this->actingAs($user)->post(route('debts.pay', $debt), ['amount' => 25])->assertInvalid(['amount']);
    $this->post(route('debts.pay', $foreign), ['amount' => 25])->assertForbidden();
    expect((float) $debt->fresh()->balance)->toBe(100.0)->and((float) $foreign->fresh()->balance)->toBe(100.0)->and(Expense::count())->toBe(0);
});

it('deducts the actual DOP cost for the existing USD conversion and stores both currencies on the receipt', function () {
    $this->mock(BpdExchangeRateService::class)->shouldReceive('getUsdSellRate')->once()->andReturn(60.50);
    $user = User::factory()->create();
    $budget = Budget::create(['user_id' => $user->id, 'title' => 'Plan DOP', 'currency' => 'DOP', 'income' => 10000, 'details' => json_encode(['remaining' => 10000])]);
    $debt = Debt::create(['user_id' => $user->id, 'name' => 'Deuda USD', 'currency' => 'USD', 'balance' => 500]);
    $this->actingAs($user)->post(route('debts.pay', $debt), ['amount' => 100])->assertSessionHasNoErrors();
    $details = $budget->fresh()->details;
    if (is_string($details)) $details = json_decode($details, true);
    expect((float) $details['remaining'])->toBe(3950.0)
        ->and((float) $details['debt_payments'][0]['amount'])->toBe(100.0)
        ->and($details['debt_payments'][0]['currency'])->toBe('USD')
        ->and((float) $details['debt_payments'][0]['budget_amount'])->toBe(6050.0)
        ->and((float) $debt->fresh()->balance)->toBe(400.0);
    $export = new \App\Exports\BudgetExport($budget->fresh());
    $paymentRow = collect($export->array())->first(fn ($row) => $row[0] === '⚔️ Ataque a Deuda');
    expect($export->headings()[2])->toBe('Monto (DOP)')
        ->and((float) $paymentRow[2])->toBe(6050.0);
});

it('backfills legacy currencies without losing existing balances during migration', function () {
    $migration = require database_path('migrations/2026_10_06_000000_add_financial_preferences.php');
    $migration->down();
    $user = User::factory()->make();
    unset($user->preferred_currency, $user->number_locale);
    $user->save();
    $budget = new Budget(['user_id' => $user->id, 'title' => 'Anterior', 'income' => 123.45, 'details' => json_encode(['remaining' => 100])]);
    unset($budget->currency);
    $budget->save();
    $expense = new Expense(['user_id' => $user->id, 'description' => 'Anterior', 'amount' => 23.45]);
    unset($expense->currency);
    $expense->save();
    $migration->up();
    expect($user->fresh()->preferred_currency)->toBe('DOP')->and($user->fresh()->number_locale)->toBe('es-DO')
        ->and($budget->fresh()->currency)->toBe('DOP')->and((float) $budget->fresh()->income)->toBe(123.45)
        ->and($expense->fresh()->currency)->toBe('DOP')->and((float) $expense->fresh()->amount)->toBe(23.45);
});


it('uses the latest budget deterministically when budgets share a creation timestamp', function () {
    $user = User::factory()->create();
    $first = Budget::create(['user_id' => $user->id, 'title' => 'Anterior DOP', 'currency' => 'DOP', 'income' => 100, 'details' => json_encode(['remaining' => 100])]);
    $latest = Budget::create(['user_id' => $user->id, 'title' => 'Nuevo EUR', 'currency' => 'EUR', 'income' => 200, 'details' => json_encode(['remaining' => 200]), 'created_at' => $first->created_at]);
    $debt = Debt::create(['user_id' => $user->id, 'name' => 'Deuda EUR', 'currency' => 'EUR', 'balance' => 50]);
    $this->actingAs($user)->post(route('debts.pay', $debt), ['amount' => 10])->assertSessionHasNoErrors();
    $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('available.currency', 'EUR')->where('available.amount', 190));
    expect(json_decode($first->fresh()->details, true)['remaining'])->toBe(100);
});
