<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Budget;
use App\Models\Expense;
use App\Services\DailyQuestEngine;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $debts = $user->debts()->where('balance', '>', 0)->get();
        $goals = $user->goals()->get();
        $budget = Budget::where('user_id', $user->id)->latest('id')->first();
        $details = $budget && is_string($budget->details)
            ? json_decode($budget->details, true) : $budget?->details;
        $remaining = isset($details['remaining']) && is_numeric($details['remaining'])
            ? (float) $details['remaining'] : null;

        $debtTotals = $debts->groupBy('currency')->map(fn ($items, $currency) => [
            'currency' => $currency, 'amount' => (float) $items->sum('balance'),
        ])->values();
        $goalTotals = $goals->groupBy('currency')->map(fn ($items, $currency) => [
            'currency' => $currency, 'saved' => (float) $items->sum('current_amount'),
            'target' => (float) $items->sum('target_amount'),
        ])->values();

        // payment_date is a recurring day of month, not a known unpaid invoice.
        $today = CarbonImmutable::today();
        $nextPayment = $debts->filter(fn ($debt) => $debt->payment_date >= 1 && $debt->payment_date <= 31)
            ->map(function ($debt) use ($today) {
                $month = $today->startOfMonth();
                $date = $month->day(min((int) $debt->payment_date, $month->daysInMonth));
                if ($date->lt($today)) {
                    $month = $month->addMonth();
                    $date = $month->day(min((int) $debt->payment_date, $month->daysInMonth));
                }
                return [
                    'id' => $debt->id, 'name' => $debt->name, 'date' => $date->toDateString(),
                    'currency' => $debt->currency,
                    'amount' => $debt->minimum_payment > 0 ? (float) $debt->minimum_payment : null,
                ];
            })->sortBy('date')->first();
        $goal = $goals->first(fn ($item) => $item->current_amount < $item->target_amount) ?? $goals->first();
        $primaryDebts = (float) $debts->where('currency', $user->preferred_currency)->sum('balance');
        $primaryGoals = $goals->where('currency', $user->preferred_currency);
        $budgetCount = Budget::where('user_id', $user->id)->count();

        return Inertia::render('Dashboard', [
            'available' => $budget ? [
                'amount' => $remaining, 'currency' => $budget->currency,
                'title' => $budget->title, 'updated_at' => $budget->updated_at->toDateString(),
            ] : null,
            'debtTotals' => $debtTotals,
            'goalTotals' => $goalTotals,
            'nextPayment' => $nextPayment,
            'featuredGoal' => $goal,
            'totalDebts' => $primaryDebts,
            'activeDebtCount' => $debts->count(),
            'totalGoalsSaved' => (float) $primaryGoals->sum('current_amount'),
            'totalGoalsTarget' => (float) $primaryGoals->sum('target_amount'),
            'budgetCount' => $budgetCount,
            'lastCapitalLibre' => $remaining,
            'combatLog' => Expense::where('user_id', $user->id)->latest('id')->take(5)->get()
                ->map(fn ($expense) => [
                    'type' => 'Gasto', 'description' => $expense->description,
                    'amount' => (float) $expense->amount, 'currency' => $expense->currency,
                    'time' => $expense->created_at->locale('es')->diffForHumans(),
                ]),
            'achievements' => Achievement::with(['users' => fn ($q) => $q->where('user_id', $user->id)])
                ->get()->map(fn ($achievement) => [
                    'id' => $achievement->id, 'name' => $achievement->name,
                    'description' => $achievement->description, 'icon_name' => $achievement->icon_name,
                    'unlocked_at' => $achievement->users->isNotEmpty(),
                ]),
            'quests' => app(DailyQuestEngine::class)->getStatus($user),
        ]);
    }
}
