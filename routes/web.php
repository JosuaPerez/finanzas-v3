<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\QuickAttackController;
use App\Http\Controllers\QuestController;
use App\Services\DailyQuestEngine;
use App\Models\Budget;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Achievement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Google OAuth Routes ────────────────────────────────────────────────────────
Route::get('/auth/google',          [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');


// Root: guests see the Landing Page, authenticated users go straight to the dashboard.
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome');
})->name('home');

// Ruta pública: Términos y Condiciones (no requiere autenticación)
Route::get('/terminos', fn () => Inertia::render('Terms'))->name('terminos');

use App\Models\Goal;

Route::get('/dashboard', function () {
    $uid  = auth()->id();
    $data = Cache::remember('dashboard_data_user_' . $uid, now()->addMinutes(15), function () use ($uid) {
        $totalDebts       = Debt::where('user_id', $uid)->where('balance', '>', 0)->sum('balance');
        $activeDebtCount  = Debt::where('user_id', $uid)->where('balance', '>', 0)->count();
        $totalGoalsSaved  = Goal::where('user_id', $uid)->sum('current_amount');
        $totalGoalsTarget = Goal::where('user_id', $uid)->sum('target_amount');
        $budgetCount      = Budget::where('user_id', $uid)->count();
        $lastBudget       = Budget::where('user_id', $uid)->latest()->first();
        $lastCapitalLibre = 0;
        if ($lastBudget) {
            $details          = is_string($lastBudget->details) ? json_decode($lastBudget->details, true) : $lastBudget->details;
            $lastCapitalLibre = $details['remaining'] ?? 0;
        }

        $combatLog = Expense::where('user_id', $uid)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($e) => [
                'type'        => 'Ataque Rápido',
                'description' => $e->description,
                'amount'      => $e->amount,
                'time'        => $e->created_at->diffForHumans(),
            ]);

        // All achievements with unlock status for this user — single query via eager load.
        // Previously: Achievement::whereHas(...) + Achievement::all() = 2 round-trips.
        // Now: one JOIN-backed eager load covers both the list and the pivot check.
        $achievements = Achievement::with(['users' => fn ($q) => $q->where('user_id', $uid)])
            ->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'name'        => $a->name,
                'description' => $a->description,
                'icon_name'   => $a->icon_name,
                'unlocked_at' => $a->users->isNotEmpty() ? true : null,
            ]);

        return [
            'totalDebts'       => (float) $totalDebts,
            'activeDebtCount'  => (int)   $activeDebtCount,
            'totalGoalsSaved'  => (float) $totalGoalsSaved,
            'totalGoalsTarget' => (float) $totalGoalsTarget,
            'budgetCount'      => (int)   $budgetCount,
            'lastCapitalLibre' => (float) $lastCapitalLibre,
            'combatLog'        => $combatLog,
            'achievements'     => $achievements,
            'quests'           => app(DailyQuestEngine::class)->getStatus(auth()->user()),
            'chartData'        => [
                'debts'   => (float) $totalDebts,
                'capital' => (float) $lastCapitalLibre,
                'goals'   => (float) $totalGoalsSaved,
            ],
        ];
    });

    return Inertia::render('Dashboard', $data);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/presupuesto', function () {
    $uid            = auth()->id();
    $misPresupuestos = Budget::where('user_id', $uid)->latest()->get();
    $totalDebts      = Debt::where('user_id', $uid)->where('balance', '>', 0)->sum('balance');

    return Inertia::render('Presupuesto', [
        'budgets'    => $misPresupuestos,
        'totalDebts' => (float) $totalDebts,
    ]);
})->middleware(['auth', 'verified'])->name('presupuesto');

Route::get('/deudas', function () {
    $uid = auth()->id();

    // 1. Deudas activas
    $misDeudas = Debt::where('user_id', $uid)->where('balance', '>', 0)->get();

    // 2. Último presupuesto → municiones
    $ultimoPresupuesto = Budget::where('user_id', $uid)->latest()->first();
    $capitalLibre = 0;
    if ($ultimoPresupuesto) {
        $details      = is_string($ultimoPresupuesto->details) ? json_decode($ultimoPresupuesto->details, true) : $ultimoPresupuesto->details;
        $capitalLibre = $details['remaining'] ?? 0;
    }

    // 3. Jefes Caídos (campaign_bosses derrotados)
    $fallenBosses = \App\Models\CampaignBoss::where('user_id', $uid)
        ->where('is_defeated', 'true')
        ->orderByDesc('updated_at')
        ->get(['id', 'name', 'experience_reward', 'updated_at']);

    return Inertia::render('Deudas', [
        'debts'             => $misDeudas,
        'ammunition'        => $capitalLibre,
        'usd_exchange_rate' => app(\App\Services\BpdExchangeRateService::class)->getUsdSellRate(),
        'fallen_bosses'     => $fallenBosses,
    ]);
})->middleware(['auth', 'verified'])->name('deudas');

// Ruta para guardar nueva deuda
Route::middleware(['auth', 'verified', 'throttle:30,1'])->group(function () {
    Route::post('/deudas', [DebtController::class, 'store'])->name('debts.store');
    Route::post('/deudas/{debt}/pagar', [DebtController::class, 'pay'])->name('debts.pay');
    Route::delete('/deudas/{debt}', [DebtController::class, 'destroy'])->name('debts.destroy');
});

Route::middleware(['auth', 'verified', 'throttle:30,1'])->group(function () {
    // Ruta para ver las metas
    Route::get('/metas', [GoalController::class, 'index'])->name('metas');
    
    // Rutas para las acciones (POST, DELETE)
    Route::post('/metas', [GoalController::class, 'store'])->name('metas.store');
    Route::delete('/metas/{goal}', [GoalController::class, 'destroy'])->name('metas.destroy');
    Route::post('/metas/{goal}/add-funds', [GoalController::class, 'addFunds'])->name('metas.add_funds');
});

Route::post('/presupuestos', [BudgetController::class, 'store'])->middleware(['auth', 'verified', 'throttle:30,1'])->name('budgets.store');

// Ruta para la nueva página de Historial
Route::get('/historial', [App\Http\Controllers\BudgetController::class, 'history'])->middleware(['auth', 'verified'])->name('historial');

// Actualizamos la ruta de exportar para que acepte un ID opcional al final ({id?})
Route::get('/presupuestos/exportar/{id?}', [App\Http\Controllers\BudgetController::class, 'export'])->middleware(['auth', 'verified'])->name('budgets.export');

Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::post('/quick-attack', [QuickAttackController::class, 'store'])->name('quick-attack.store');
    Route::post('/quests/claim', [QuestController::class, 'claim'])->name('quests.claim');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
