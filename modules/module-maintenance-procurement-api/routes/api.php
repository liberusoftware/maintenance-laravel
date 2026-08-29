<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Procurement\Api\Http\Controllers\PurchaseRequestController;
use Liberu\Modules\Maintenance\Procurement\Api\Http\Controllers\VendorContractController;
use Liberu\Modules\Maintenance\Procurement\Api\Http\Controllers\VendorPerformanceEvaluationController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/procurement')->group(function (): void {
    Route::get('/contracts', [VendorContractController::class, 'index']);
    Route::post('/contracts', [VendorContractController::class, 'store']);
    Route::get('/contracts/{vendorContract}', [VendorContractController::class, 'show']);
    Route::patch('/contracts/{vendorContract}', [VendorContractController::class, 'update']);
    Route::post('/contracts/{vendorContract}/transition', [VendorContractController::class, 'transition']);
    Route::delete('/contracts/{vendorContract}', [VendorContractController::class, 'destroy']);
    Route::get('/evaluations', [VendorPerformanceEvaluationController::class, 'index']);
    Route::post('/evaluations', [VendorPerformanceEvaluationController::class, 'store']);
    Route::get('/evaluations/{vendorPerformanceEvaluation}', [VendorPerformanceEvaluationController::class, 'show']);
    Route::delete('/evaluations/{vendorPerformanceEvaluation}', [VendorPerformanceEvaluationController::class, 'destroy']);
    Route::get('/', [PurchaseRequestController::class, 'index']);
    Route::post('/', [PurchaseRequestController::class, 'store']);
    Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
    Route::patch('/{purchaseRequest}', [PurchaseRequestController::class, 'update']);
    Route::delete('/{purchaseRequest}', [PurchaseRequestController::class, 'destroy']);
    Route::post('/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve']);
    Route::post('/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject']);
    Route::post('/{purchaseRequest}/transitions', [PurchaseRequestController::class, 'transition']);
});
