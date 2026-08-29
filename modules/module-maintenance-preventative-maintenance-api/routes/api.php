<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Api\Http\Controllers\MaintenancePlanController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/preventative-maintenance')->group(function (): void {
    Route::get('/', [MaintenancePlanController::class, 'index']);
    Route::post('/', [MaintenancePlanController::class, 'store']);
    Route::get('/{maintenancePlan}', [MaintenancePlanController::class, 'show']);
    Route::patch('/{maintenancePlan}', [MaintenancePlanController::class, 'update']);
    Route::post('/{maintenancePlan}/complete', [MaintenancePlanController::class, 'complete']);
    Route::delete('/{maintenancePlan}', [MaintenancePlanController::class, 'destroy']);
});
