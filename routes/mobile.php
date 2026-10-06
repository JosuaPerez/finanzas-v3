<?php

use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\FinancePageController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuickAttackController;
use Illuminate\Support\Facades\Route;

// These routes require bearer tokens. ConfigureMobileApi disables cookie authentication.
Route::prefix('api/mobile/v1')->group(function () {
    Route::middleware('throttle:6,1')->group(function () {
        Route::post('login', [MobileAuthController::class, 'login']);
        Route::post('register', [MobileAuthController::class, 'register']);
        Route::post('forgot-password', [PasswordResetLinkController::class, 'store']);
        Route::post('reset-password', [NewPasswordController::class, 'store']);
    });
    Route::middleware(['auth:sanctum', 'mobile-ability:mobile', 'throttle:60,1', 'financial-submission'])->group(function () {
        Route::get('dashboard', DashboardController::class);
        Route::get('presupuesto', [FinancePageController::class, 'budget']);
        Route::get('deudas', [FinancePageController::class, 'debts']);
        Route::get('metas', [GoalController::class, 'index']);
        Route::get('historial', [BudgetController::class, 'history']);
        Route::get('presupuestos/exportar/{id?}', [BudgetController::class, 'export']);
        Route::get('profile', [ProfileController::class, 'edit']);
        Route::post('presupuestos', [BudgetController::class, 'store']);
        Route::post('quick-attack', [QuickAttackController::class, 'store']);
        Route::post('deudas', [DebtController::class, 'store']);
        Route::post('deudas/{debt}/pagar', [DebtController::class, 'pay']);
        Route::delete('deudas/{debt}', [DebtController::class, 'destroy']);
        Route::post('metas', [GoalController::class, 'store']);
        Route::post('metas/{goal}/add-funds', [GoalController::class, 'addFunds']);
        Route::delete('metas/{goal}', [GoalController::class, 'destroy']);
        Route::post('quests/claim', [QuestController::class, 'claim']);
        Route::patch('profile/financial-preferences', [ProfileController::class, 'financialPreferences']);
        Route::patch('profile', [ProfileController::class, 'update']);
        Route::put('password', [PasswordController::class, 'update']);
        Route::delete('profile', [ProfileController::class, 'destroy']);
        Route::post('logout', [MobileAuthController::class, 'logout']);
    });
});
