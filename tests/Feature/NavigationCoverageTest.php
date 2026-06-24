<?php

use App\Models\Budget;
use App\Models\Debt;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('passes defeated bosses and budgets to the Historial Inertia view while excluding active debts', function () {
    $user = User::factory()->create();

    Budget::create([
        'user_id'              => $user->id,
        'title'                => 'Presupuesto Historial',
        'income'               => 50000,
        'fixed_expenses_total' => 20000,
    ]);

    // Active debt (balance > 0) -> should be excluded from defeated_bosses
    Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Deuda Activa',
        'balance'         => 10000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 10,
        'minimum_payment' => 1000,
    ]);

    // Defeated boss (balance <= 0) -> should be included in defeated_bosses
    Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Jefe Vencido',
        'balance'         => 0,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 10,
        'minimum_payment' => 1000,
    ]);

    $this->actingAs($user)
        ->get('/historial')
        ->assertStatus(200)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Historial')
            ->has('budgets', 1)
            ->has('defeated_bosses', 1)
            ->where('defeated_bosses.0.name', 'Jefe Vencido')
        );
});

it('passes active debts and usd_exchange_rate to the Deudas Inertia view while excluding defeated bosses', function () {
    $user = User::factory()->create();

    // Active debt (balance > 0) -> should be included in misDeudas / debts prop
    Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Deuda Activa Deudas',
        'balance'         => 15000,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 10,
        'minimum_payment' => 1000,
    ]);

    // Defeated boss (balance <= 0) -> should be excluded from Deudas view
    Debt::create([
        'user_id'         => $user->id,
        'name'            => 'Jefe Vencido Deudas',
        'balance'         => 0,
        'type'            => 'loan',
        'currency'        => 'DOP',
        'interest_rate'   => 10,
        'minimum_payment' => 1000,
    ]);

    $this->actingAs($user)
        ->get('/deudas')
        ->assertStatus(200)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Deudas')
            ->has('debts', 1)
            ->where('debts.0.name', 'Deuda Activa Deudas')
            ->has('usd_exchange_rate')
        );
});
