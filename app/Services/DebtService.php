<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Budget;
use Illuminate\Support\Facades\DB;

class DebtService
{
    /**
     * Apply a payment to a debt.
     * Reduces the balance (clamped to 0), updates the budget's remaining
     * capital, and records the payment receipt inside budget->details.
     *
     * @param  Debt   $debt
     * @param  float  $amount  The payment amount (must be > 0).
     * @return bool   True if the debt is now fully paid off (balance == 0).
     */
    public function applyPayment(Debt $debt, float $amount, ?float $budgetCost = null): bool
    {
        return DB::transaction(function () use ($debt, $amount, $budgetCost) {
            \App\Models\User::whereKey($debt->user_id)->lockForUpdate()->firstOrFail();
            $locked = Debt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            $debt->setRawAttributes($locked->getAttributes(), true);
            if ($budgetCost !== null && $amount > (float) $debt->balance) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'El pago supera el saldo pendiente de la deuda.']);
            }
            // 1. Deduct from the debt balance, floor at 0
            $debt->balance = max(0, $debt->balance - $amount);
            $debt->save();

            // 2. Reflect payment against the active budget's remaining capital
            $budget = Budget::where('user_id', $debt->user_id)->latest('id')->lockForUpdate()->first();

            if ($budget && ($budget->currency === $debt->currency || $budgetCost !== null)) {
                $details = is_string($budget->details)
                    ? json_decode($budget->details, true)
                    : (array) $budget->details;

                if (($details['remaining'] ?? 0) < 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'municion' => 'Tu presupuesto tiene un disponible negativo. Revísalo antes de registrar un pago.',
                    ]);
                }
                if (($details['remaining'] ?? 0) > 0 && ($budgetCost ?? $amount) > $details['remaining']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['municion' => 'El disponible del presupuesto cambió. Revisa el importe del pago.']);
                }

                // Deduct from free capital
                $details['remaining'] = round(($details['remaining'] ?? 0) - ($budgetCost ?? $amount), 2);

                // Append payment receipt
                $details['debt_payments'][] = [
                    'name'   => $debt->name,
                    'amount' => $amount,
                    'currency' => $debt->currency,
                    'budget_amount' => $budgetCost ?? $amount,
                    'budget_currency' => $budget->currency,
                    'paid_at' => now()->toIso8601String(),
                ];

                $budget->details = $details;
                $budget->save();
            }

            return $debt->balance <= 0;
        }, 3);
    }

    /**
     * Calculate the HP (health-bar) stats for a debt.
     * Mirrors the getHPStats() function previously living in Deudas.vue.
     *
     * Rules (RPG metaphor):
     *  - Loans  → maxHP = original_amount (or balance if unset)
     *  - Cards  → maxHP = credit_limit * (1 + overdraft_percentage / 100)
     *  - If balance exceeds the computed maxHP, maxHP is raised to match
     *    (avoids a broken bar due to accumulated interest).
     *
     * isCritical: debt HP% > 80% (boss is almost at full health → danger).
     *
     * @param  Debt  $debt
     * @return array{current: float, max: float, percent: float, isCritical: bool}
     */
    public function getHPStats(Debt $debt): array
    {
        $balance = (float) $debt->balance;

        $maxHP = match ($debt->type) {
            'loan'        => ($debt->original_amount > 0)
                                ? (float) $debt->original_amount
                                : $balance,
            'credit_card' => ($debt->credit_limit > 0)
                                ? (float) $debt->credit_limit
                                  * (1 + ((float) ($debt->overdraft_percentage ?? 0) / 100))
                                : $balance,
            default       => $balance,
        };

        // Guard: interest may push balance beyond computed maxHP
        if ($balance > $maxHP) {
            $maxHP = $balance;
        }

        $percent = $maxHP > 0 ? ($balance / $maxHP) * 100 : 100.0;

        return [
            'current'    => $balance,
            'max'        => $maxHP,
            'percent'    => min(100.0, $percent),
            'isCritical' => $percent > 80,
        ];
    }
}
