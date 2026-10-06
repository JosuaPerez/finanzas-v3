<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\CampaignBoss;
use App\Models\Debt;
use Inertia\Inertia;
use Inertia\Response;

class FinancePageController extends Controller
{
    public function budget(): Response
    {
        $uid = auth()->id();
        $misPresupuestos = Budget::where('user_id', $uid)->latest('id')->get();
        $totalDebts = Debt::where('user_id', $uid)->where('balance', '>', 0)
            ->where('currency', auth()->user()->preferred_currency)->sum('balance');

        return Inertia::render('Presupuesto', [
            'budgets' => $misPresupuestos,
            'totalDebts' => (float) $totalDebts,
            'debtTotals' => Debt::where('user_id', $uid)->where('balance', '>', 0)->get()->groupBy('currency')->map(fn ($items) => (float) $items->sum('balance')),
        ]);
    }

    public function debts(): Response
    {
        $uid = auth()->id();

        // 1. Deudas activas
        $misDeudas = Debt::where('user_id', $uid)->where('balance', '>', 0)->get();

        // 2. Último presupuesto → municiones
        $ultimoPresupuesto = Budget::where('user_id', $uid)->latest('id')->first();
        $capitalLibre = 0;
        if ($ultimoPresupuesto) {
            $details = is_string($ultimoPresupuesto->details) ? json_decode($ultimoPresupuesto->details, true) : $ultimoPresupuesto->details;
            $capitalLibre = $details['remaining'] ?? 0;
        }

        // 3. Jefes Caídos (campaign_bosses derrotados)
        $fallenBosses = CampaignBoss::where('user_id', $uid)
            ->where('is_defeated', 'true')
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'experience_reward', 'updated_at']);

        return Inertia::render('Deudas', [
            'debts' => $misDeudas,
            'ammunition' => $capitalLibre,
            'budget_currency' => $ultimoPresupuesto?->currency,
            'usd_exchange_rate' => null,
            'fallen_bosses' => $fallenBosses,
        ]);
    }
}
