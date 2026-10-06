<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\QuickAttackController;
use App\Http\Controllers\QuestController;
use App\Models\Budget;
use App\Models\Debt;
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


Route::get('/dashboard', App\Http\Controllers\DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/presupuesto', function () {
    $uid            = auth()->id();
    $misPresupuestos = Budget::where('user_id', $uid)->latest('id')->get();
    $totalDebts      = Debt::where('user_id', $uid)->where('balance', '>', 0)
        ->where('currency', auth()->user()->preferred_currency)->sum('balance');

    return Inertia::render('Presupuesto', [
        'budgets'    => $misPresupuestos,
        'totalDebts' => (float) $totalDebts,
        'debtTotals' => Debt::where('user_id', $uid)->where('balance', '>', 0)->get()->groupBy('currency')->map(fn ($items) => (float) $items->sum('balance')),
    ]);
})->middleware(['auth', 'verified'])->name('presupuesto');

Route::get('/deudas', function () {
    $uid = auth()->id();

    // 1. Deudas activas
    $misDeudas = Debt::where('user_id', $uid)->where('balance', '>', 0)->get();

    // 2. Último presupuesto → municiones
    $ultimoPresupuesto = Budget::where('user_id', $uid)->latest('id')->first();
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
        'budget_currency'   => $ultimoPresupuesto?->currency,
        'usd_exchange_rate' => $ultimoPresupuesto?->currency === 'DOP' && $misDeudas->contains('currency', 'USD')
            ? app(\App\Services\BpdExchangeRateService::class)->getUsdSellRate() : null,
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
    Route::patch('/profile/financial-preferences', [ProfileController::class, 'financialPreferences'])->name('profile.financial-preferences');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
