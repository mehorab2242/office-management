<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\MonthlyReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', EnsureActiveUser::class])->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('expenses', ExpenseController::class);
    Route::post('/expenses/{expense}/attachments', [AttachmentController::class, 'store']);
    Route::get('/expenses/{expense}/attachments/{attachment}', [AttachmentController::class, 'show']);
    Route::delete('/expenses/{expense}/attachments/{attachment}', [AttachmentController::class, 'destroy']);
    Route::get('/dashboard', DashboardController::class);
    Route::get('/reports/monthly', MonthlyReportController::class);
    Route::get('/audit-logs', AuditLogController::class);
    Route::apiResource('users', UserController::class)->except('destroy');
});
