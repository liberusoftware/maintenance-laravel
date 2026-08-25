<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\WorkOrders\Api\Http\Controllers\WorkOrderController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/work-orders')->group(function (): void {
    Route::get('/', [WorkOrderController::class, 'index']);
    Route::post('/', [WorkOrderController::class, 'store']);
    Route::get('/{workOrder}', [WorkOrderController::class, 'show']);
});
