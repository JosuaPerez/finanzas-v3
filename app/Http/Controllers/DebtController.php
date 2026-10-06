<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Services\BpdExchangeRateService;
use App\Services\CombatService;
use App\Services\DebtService;
use App\Jobs\EvaluateAchievementsJob;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class DebtController extends Controller
{

    public function __construct(
        private readonly DebtService            $debtService,
        private readonly BpdExchangeRateService $rateService,
        private readonly CombatService          $combatService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        Cache::forget('dashboard_data_user_' . $request->user()->id);

        $request->validate([
            'name'                  => 'required|string|max:255',
            'balance'               => 'required|numeric|min:0',
            'interest_rate'         => 'required|numeric|min:0',
            'minimum_payment'       => 'required|numeric|min:0',
            'type'                  => 'required|string|in:loan,credit_card',
            'currency'              => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(config('finance.currencies')))],
            'credit_limit'          => 'nullable|numeric|min:0',
            'cutoff_date'           => 'nullable|integer|min:1|max:31',
            'payment_date'          => 'nullable|integer|min:1|max:31',
            'original_amount'       => 'nullable|numeric|min:0',
            'overdraft_percentage'  => 'nullable|numeric|min:0|max:100',
            'fecha_inicio'          => 'nullable|date',
            'plazo_original_meses'  => 'nullable|integer|min:1|max:600',
        ]);

        $request->user()->debts()->create([
            'name'                  => $request->name,
            'balance'               => $request->balance,
            'interest_rate'         => $request->interest_rate        ?? 0,
            'minimum_payment'       => $request->minimum_payment      ?? 0,
            'type'                  => $request->type                 ?? 'loan',
            'credit_limit'          => $request->credit_limit,
            'cutoff_date'           => $request->cutoff_date,
            'payment_date'          => $request->payment_date,
            'original_amount'       => $request->original_amount,
            'currency'              => $request->currency             ?? 'DOP',
            'overdraft_percentage'  => $request->overdraft_percentage ?? 0,
            'fecha_inicio'          => $request->fecha_inicio         ?: null,
            'plazo_original_meses'  => $request->plazo_original_meses ?: null,
        ]);

        return redirect()->route('deudas');
    }

    public function pay(Request $request, Debt $debt): RedirectResponse
    {
        Gate::authorize('pay', $debt);

        Cache::forget('dashboard_data_user_' . $request->user()->id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        // ── Ammunition (Capital Libre) guard ─────────────────────────────────
        // Fetch available capital from the user's latest budget.
        // If no budget exists, $capitalLibre stays 0 and the guard is skipped
        // so users without a budget aren't hard-blocked.
        $budget       = \App\Models\Budget::where('user_id', $request->user()->id)
            ->latest('id')
            ->first();
        $details      = $budget
            ? (is_string($budget->details)
                ? json_decode($budget->details, true)
                : (array) $budget->details)
            : [];
        $capitalLibre = (float) ($details['remaining'] ?? 0);

        // Only the existing USD-debt / DOP-budget pair has a conversion.
        // Same-currency financial amounts are never converted.
        $sameCurrency = ! $budget || $budget->currency === $debt->currency;
        $legacyConversion = $budget && $budget->currency === 'DOP' && $debt->currency === 'USD';
        if (! $sameCurrency && ! $legacyConversion) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => 'La deuda y el presupuesto tienen monedas diferentes. No se realizará una conversión automática.',
            ]);
        }
        $budgetCost = (float) $validated['amount'];
        if ($legacyConversion) {
            $budgetCost *= $this->rateService->getUsdSellRate();
        }

        if ($capitalLibre > 0 && $budgetCost > $capitalLibre) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'municion' => 'Munición insuficiente (Capital Libre) para este ataque.',
            ]);
        }

        $this->debtService->applyPayment($debt, (float) $validated['amount'], $budgetCost);

        $user = $request->user();

        // ── RPG Combat: real debt payment = real boss damage ─────────────────
        // Preserve the existing USD-to-DOP normalization for RPG damage.
        // It does not change the currency of the financial receipt.
        // CombatService handles defeat detection and XP award internally.
        $damage = $debt->currency === 'USD' && ! $legacyConversion
            ? (float) $validated['amount'] * $this->rateService->getUsdSellRate()
            : $budgetCost;
        $this->combatService->processAttack($user, $damage);

        // ── Achievement checks (async) ────────────────────────────────────────
        EvaluateAchievementsJob::dispatch($user, 'debt_payment_made');
        $debt->refresh();
        if ($debt->balance <= 0) {
            EvaluateAchievementsJob::dispatch($user, 'debt_eliminated');
        }

        return redirect()->back()->with('success', 'Pago registrado. Saldo de la deuda y presupuesto actualizados cuando existe presupuesto.');
    }

    public function destroy(Debt $debt): RedirectResponse
    {
        Gate::authorize('delete', $debt);

        Cache::forget('dashboard_data_user_' . auth()->id());

        $debt->delete();

        return redirect()->back();
    }
}