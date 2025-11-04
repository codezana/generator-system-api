<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    IndexController,
    TypeController,
    GeneratorsController,
    ExpensesController,
    GenExpensesController,
    DebtController,
    AmperesController,
    ReportsController
};
// Authentication Routes
Route::post('login', [AuthController::class, 'login']);

// Ensure API always responds in JSON
Route::middleware(['Json'])->group(function () {


    Route::middleware('auth:sanctum')->group(function () {

        // Authenticated User Actions
        Route::post('logout', [AuthController::class, 'logout']);

        // User Management (Only for super_admin & manager)
        Route::prefix('user')->middleware(['Role:super_admin,manager'])->group(function () {
            Route::get('/', [AuthController::class, 'index']);
            Route::get('{id}', [AuthController::class, 'show']);
            Route::post('store', [AuthController::class, 'store']);
            Route::put('{id}', [AuthController::class, 'update']);
            Route::delete('{id}', [AuthController::class, 'destroy']);
            Route::post('reset/{id}', [AuthController::class, 'reset']);
        });

        // Change Password
        Route::post('change-password', [AuthController::class, 'changePassword']);

        // Admin Routes (Only for super_admin & manager)
        Route::middleware(['Role:super_admin,manager'])->group(function () {
            Route::apiResource('expenses', ExpensesController::class);
        });

        // General API Endpoints
        Route::apiResource('generators', GeneratorsController::class);
        Route::apiResource('amperes', AmperesController::class);

        Route::get('debts', [DebtController::class, 'debt']);
        Route::get('show-debt/{id}', [DebtController::class, 'show']);
        Route::post('store-debt', [DebtController::class, 'store']);
        Route::put('update-debt/{id}', [DebtController::class, 'update']);
        Route::delete('destroy-debt/{id}', [DebtController::class, 'destroy']);

        Route::apiResource('expense-generators', GenExpensesController::class);
        Route::get('/dashboard', [IndexController::class, 'index']);
        Route::get('/dashboard/debt', [IndexController::class, 'getRepayment']);
        Route::apiResource('types', TypeController::class);

        //Reports
        Route::prefix('report')->group(function () {
            Route::get('name', [ReportsController::class, 'generatorNameByUserRole']);
            Route::get('ampere-usage', [ReportsController::class, 'ampereUsageReport']);
            Route::get('generator-usage', [ReportsController::class, 'geExpenseUsageReport']);
            Route::get('expense-usage', [ReportsController::class, 'expenseUsageReport']);
        });
    });
});
