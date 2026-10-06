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

Route::get('/presupuesto', [App\Http\Controllers\FinancePageController::class, 'budget'])->middleware(['auth', 'verified'])->name('presupuesto');

Route::get('/deudas', [App\Http\Controllers\FinancePageController::class, 'debts'])->middleware(['auth', 'verified'])->name('deudas');

// Ruta para guardar nueva deuda
Route::middleware(['auth', 'verified', 'throttle:30,1', 'financial-submission'])->group(function () {
    Route::post('/deudas', [DebtController::class, 'store'])->name('debts.store');
    Route::post('/deudas/{debt}/pagar', [DebtController::class, 'pay'])->name('debts.pay');
    Route::delete('/deudas/{debt}', [DebtController::class, 'destroy'])->name('debts.destroy');
});

Route::middleware(['auth', 'verified', 'throttle:30,1', 'financial-submission'])->group(function () {
    // Ruta para ver las metas
    Route::get('/metas', [GoalController::class, 'index'])->name('metas');
    
    // Rutas para las acciones (POST, DELETE)
    Route::post('/metas', [GoalController::class, 'store'])->name('metas.store');
    Route::delete('/metas/{goal}', [GoalController::class, 'destroy'])->name('metas.destroy');
    Route::post('/metas/{goal}/add-funds', [GoalController::class, 'addFunds'])->name('metas.add_funds');
});

Route::post('/presupuestos', [BudgetController::class, 'store'])->middleware(['auth', 'verified', 'throttle:30,1', 'financial-submission'])->name('budgets.store');

// Ruta para la nueva página de Historial
Route::get('/historial', [App\Http\Controllers\BudgetController::class, 'history'])->middleware(['auth', 'verified'])->name('historial');

// Actualizamos la ruta de exportar para que acepte un ID opcional al final ({id?})
Route::get('/presupuestos/exportar/{id?}', [App\Http\Controllers\BudgetController::class, 'export'])->middleware(['auth', 'verified'])->name('budgets.export');

Route::middleware(['auth', 'throttle:30,1', 'financial-submission'])->group(function () {
    Route::post('/quick-attack', [QuickAttackController::class, 'store'])->name('quick-attack.store');
    Route::post('/quests/claim', [QuestController::class, 'claim'])->name('quests.claim');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile/financial-preferences', [ProfileController::class, 'financialPreferences'])->name('profile.financial-preferences');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

require __DIR__.'/mobile.php';
