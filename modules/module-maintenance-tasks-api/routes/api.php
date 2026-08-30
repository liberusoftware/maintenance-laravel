<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Tasks\Api\Http\Controllers\MaintenanceTaskController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/tasks')->group(function (): void {
    Route::get('/', [MaintenanceTaskController::class, 'index']);
    Route::post('/', [MaintenanceTaskController::class, 'store']);
    Route::post('/{task}/complete', [MaintenanceTaskController::class, 'complete']);
    Route::get('/{task}', [MaintenanceTaskController::class, 'show']);
    Route::patch('/{task}', [MaintenanceTaskController::class, 'update']);
    Route::delete('/{task}', [MaintenanceTaskController::class, 'destroy']);
});
