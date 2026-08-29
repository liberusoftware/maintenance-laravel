<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Procurement\Api\Http\Controllers\PurchaseRequestController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/procurement')->group(function (): void {
    Route::get('/', [PurchaseRequestController::class, 'index']);
    Route::post('/', [PurchaseRequestController::class, 'store']);
    Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
    Route::post('/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve']);
});
