<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\BudgetController;
use App\Models\Budget;
use App\Models\Debt;
use App\Http\Controllers\DebtController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    // Buscamos los presupuestos del usuario actual, ordenados del más reciente al más viejo
    $misPresupuestos = Budget::where('user_id', auth()->id())->latest()->get();

    // Se los enviamos a la vista 'Dashboard'
    return Inertia::render('Dashboard', [
        'budgets' => $misPresupuestos
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/deudas', function () {
    // Buscamos las deudas del usuario
    $misDeudas = Debt::where('user_id', auth()->id())->get();
    return Inertia::render('Deudas', ['debts' => $misDeudas]);
})->middleware(['auth', 'verified'])->name('deudas');

// Ruta para guardar nueva deuda
Route::post('/deudas', [DebtController::class, 'store'])->middleware(['auth', 'verified'])->name('debts.store');
Route::post('/deudas/{debt}/pagar', [DebtController::class, 'pay'])->middleware(['auth', 'verified'])->name('debts.pay');
Route::delete('/deudas/{debt}', [DebtController::class, 'destroy'])->middleware(['auth', 'verified'])->name('debts.destroy');

Route::get('/metas', function () {
    return Inertia::render('Metas');
})->middleware(['auth', 'verified'])->name('metas');

Route::post('/presupuestos', [BudgetController::class, 'store'])->middleware(['auth', 'verified'])->name('budgets.store');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
