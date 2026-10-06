<?php

use App\Models\Budget;
use App\Models\Debt;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Str;

it('deduplicates debt payments and goal deposits before balance validation or xp rewards', function () {
    $user = User::factory()->create(['preferred_currency' => 'MXN']);
    $budget = Budget::create(['user_id' => $user->id, 'title' => 'Plan', 'currency' => 'MXN', 'income' => 100, 'details' => json_encode(['remaining' => 100])]);
    $debt = Debt::create(['user_id' => $user->id, 'name' => 'Deuda', 'currency' => 'MXN', 'balance' => 25]);
    $pay = ['amount' => 25, 'request_id' => (string) Str::uuid()];
    $this->actingAs($user)->post('/deudas/'.$debt->id.'/pagar', $pay)->assertSessionHasNoErrors();
    $xp = $user->fresh()->current_xp;
    $this->post('/deudas/'.$debt->id.'/pagar', $pay)->assertSessionHasNoErrors();
    expect((float) $debt->fresh()->balance)->toBe(0.0)->and(json_decode($budget->fresh()->details, true)['remaining'])->toBe(75)->and($user->fresh()->current_xp)->toBe($xp);
    $goal = Goal::create(['user_id' => $user->id, 'name' => 'Ahorro', 'currency' => 'MXN', 'target_amount' => 100, 'current_amount' => 0]);
    $save = ['amount' => 25, 'request_id' => (string) Str::uuid()];
    $this->post('/metas/'.$goal->id.'/add-funds', $save)->assertSessionHasNoErrors();
    $this->post('/metas/'.$goal->id.'/add-funds', $save)->assertSessionHasNoErrors();
    expect((float) $goal->fresh()->current_amount)->toBe(25.0);
    $this->post('/metas/'.$goal->id.'/add-funds', [...$save, 'amount' => 30])->assertSessionHasErrors('request_id');
});

it('does not create another budget when a successful submission is retried', function () {
    $user = User::factory()->create();
    $data = ['title' => 'Plan', 'income' => 100, 'fixed_expenses_total' => 0, 'currency' => 'MXN', 'details' => ['fixed' => []], 'request_id' => (string) Str::uuid()];
    $this->actingAs($user)->post('/presupuestos', $data)->assertSessionHasNoErrors();
    $this->post('/presupuestos', $data)->assertSessionHasNoErrors();
    expect(Budget::count())->toBe(1);
    $this->actingAs(User::factory()->create())->post('/presupuestos', $data)->assertSessionHasErrors('request_id');
    expect(Budget::count())->toBe(1);
});
