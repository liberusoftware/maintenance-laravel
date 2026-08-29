<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\WorkOrders\Api\Http\Controllers\WorkOrderController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/work-orders')->group(function (): void {
    Route::get('/', [WorkOrderController::class, 'index']);
    Route::post('/', [WorkOrderController::class, 'store']);
    Route::get('/{workOrder}', [WorkOrderController::class, 'show']);
    Route::patch('/{workOrder}', [WorkOrderController::class, 'update']);
    Route::delete('/{workOrder}', [WorkOrderController::class, 'destroy']);
    Route::post('/{workOrder}/transitions', [WorkOrderController::class, 'transition']);
    Route::get('/{workOrder}/comments', [WorkOrderController::class, 'comments']);
    Route::post('/{workOrder}/comments', [WorkOrderController::class, 'comment']);
    Route::get('/{workOrder}/dependencies', [WorkOrderController::class, 'dependencies']);
    Route::post('/{workOrder}/dependencies', [WorkOrderController::class, 'addDependency']);
    Route::delete('/{workOrder}/dependencies/{dependency}', [WorkOrderController::class, 'removeDependency']);
    Route::get('/{workOrder}/evidence', [WorkOrderController::class, 'evidence']);
    Route::post('/{workOrder}/evidence', [WorkOrderController::class, 'addEvidence']);
    Route::delete('/{workOrder}/evidence/{evidence}', [WorkOrderController::class, 'removeEvidence']);
});
