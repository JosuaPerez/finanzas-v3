<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ProfileController;
use App\Models\Budget;
use App\Models\Debt;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::get('/dashboard', function () {
    // Buscamos los presupuestos del usuario actual, ordenados del más reciente al más viejo
    $misPresupuestos = Budget::where('user_id', auth()->id())->latest()->get();

    // Se los enviamos a la vista 'Dashboard'
    return Inertia::render('Dashboard', [
        'budgets' => $misPresupuestos,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/deudas', function () {
    // 1. Buscamos las deudas
    $misDeudas = Debt::where('user_id', auth()->id())->get();

    // 2. Buscamos el último presupuesto guardado para extraer las municiones
    $ultimoPresupuesto = Budget::where('user_id', auth()->id())->latest()->first();
    $capitalLibre = 0;

    if ($ultimoPresupuesto) {
        // Como guardamos los detalles en JSON, lo decodificamos
        $details = is_string($ultimoPresupuesto->details) ? json_decode($ultimoPresupuesto->details, true) : $ultimoPresupuesto->details;
        $capitalLibre = $details['remaining'] ?? 0;
    }

    // 3. Enviamos todo a Vue
    return Inertia::render('Deudas', [
        'debts' => $misDeudas,
        'ammunition' => $capitalLibre, // <-- Aquí van las municiones
    ]);
})->middleware(['auth', 'verified'])->name('deudas');

// Ruta para guardar nueva deuda
Route::post('/deudas', [DebtController::class, 'store'])->middleware(['auth', 'verified'])->name('debts.store');
Route::post('/deudas/{debt}/pagar', [DebtController::class, 'pay'])->middleware(['auth', 'verified'])->name('debts.pay');
Route::delete('/deudas/{debt}', [DebtController::class, 'destroy'])->middleware(['auth', 'verified'])->name('debts.destroy');

Route::get('/metas', function () {
    return Inertia::render('Metas');
})->middleware(['auth', 'verified'])->name('metas');

Route::post('/presupuestos', [BudgetController::class, 'store'])->middleware(['auth', 'verified'])->name('budgets.store');

// Ruta para la nueva página de Historial
Route::get('/historial', [App\Http\Controllers\BudgetController::class, 'history'])->middleware(['auth', 'verified'])->name('historial');

// Actualizamos la ruta de exportar para que acepte un ID opcional al final ({id?})
Route::get('/presupuestos/exportar/{id?}', [App\Http\Controllers\BudgetController::class, 'export'])->middleware(['auth', 'verified'])->name('budgets.export');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
