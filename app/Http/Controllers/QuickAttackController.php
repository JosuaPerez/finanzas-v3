<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Budget;
use App\Models\User;
use App\Jobs\EvaluateAchievementsJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuickAttackController extends Controller
{
    /**
     * Record a quick expense (Quick Attack) and reward the user with XP.
     */
    public function store(Request $request): RedirectResponse
    {
        Cache::forget('dashboard_data_user_' . $request->user()->id);

        $validated = $request->validate([
            'monto'       => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'descripcion' => ['required', 'string', 'max:255'],
            'currency' => ['sometimes', 'required', 'string', \Illuminate\Validation\Rule::in(array_keys(config('finance.currencies')))],
            'already_budgeted' => ['sometimes', 'boolean'],
            'request_id' => ['sometimes', 'nullable', 'uuid'],
        ]);

        $expense = DB::transaction(function () use ($request, $validated) {
            // Serialize repeated submissions from this user, including requests
            // with no budget. The unique request ID also prevents duplicate inserts.
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $requestId = $validated['request_id'] ?? null;
            if ($requestId && ($existing = Expense::where('request_id', $requestId)->first())) {
                if ($existing->user_id !== $user->id) {
                    throw ValidationException::withMessages(['request_id' => 'Vuelve a abrir el formulario para registrar este gasto.']);
                }
                return $existing;
            }
            $budget = Budget::where('user_id', $user->id)->latest('id')->lockForUpdate()->first();
            $currency = $validated['currency'] ?? $user->preferred_currency;
            $details = $budget && is_string($budget->details)
                ? json_decode($budget->details, true) : $budget?->details;
            if ($budget && $currency !== $budget->currency) {
                throw ValidationException::withMessages(['currency' => 'Elige la moneda de tu presupuesto. No se convierten importes automáticamente.']);
            }
            if ($budget && (! isset($details['remaining']) || ! is_numeric($details['remaining']))) {
                throw ValidationException::withMessages(['monto' => 'Crea un presupuesto con un disponible calculado antes de registrar este gasto.']);
            }
            $deduct = $budget && ! ($validated['already_budgeted'] ?? false);
            $expense = Expense::create([
                'user_id' => $user->id, 'amount' => $validated['monto'],
                'currency' => $currency, 'description' => $validated['descripcion'],
                'budget_id' => $budget?->id, 'budget_deducted' => (bool) $deduct,
                'request_id' => $requestId,
            ]);
            if ($budget) {
                if ($deduct) {
                    // Keep negative availability: an actual expense can exceed
                    // the plan. Round cents rather than hiding overspending at zero.
                    $details['remaining'] = (round((float) $details['remaining'] * 100)
                        - round((float) $validated['monto'] * 100)) / 100;
                }
                $details['expenses'][] = [
                    'id' => $expense->id, 'description' => $expense->description,
                    'amount' => (float) $expense->amount, 'currency' => $currency,
                    'deducted' => (bool) $deduct,
                ];
                $budget->details = $details;
                $budget->save();
            }
            $user->addXp(15);

            return $expense;
        }, 3);

        if ($expense->wasRecentlyCreated) {
            EvaluateAchievementsJob::dispatch($request->user()->fresh(), 'expenses_registered');
        }

        return redirect()
            ->back()
            ->with('success', $expense->budget_deducted
                ? 'Gasto registrado y descontado del presupuesto.'
                : ($expense->budget_id ? 'Gasto registrado. Ya estaba incluido en los gastos fijos.' : 'Gasto registrado. Sin presupuesto activo, no se descontó de un disponible.'));
    }
}
