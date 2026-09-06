<?php

use Illuminate\Support\Facades\Route;
use Modules\Expenses\Http\Controllers\ExpenseApiController;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('expenses')->group(function () {
        Route::post('/', [ExpenseApiController::class, 'store']);
        Route::get('/', [ExpenseApiController::class, 'index']);
        Route::get('{expense}', [ExpenseApiController::class, 'show']);
        Route::put('{expense}', [ExpenseApiController::class, 'update']);
        Route::delete('{expense}', [ExpenseApiController::class, 'destroy']);
        Route::put('{expense}/approve', [ExpenseApiController::class, 'approve']);
        Route::put('{expense}/reject', [ExpenseApiController::class, 'reject']);
    });
});
