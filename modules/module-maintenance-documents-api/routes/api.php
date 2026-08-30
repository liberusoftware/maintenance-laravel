<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Documents\Api\Http\Controllers\MaintenanceDocumentController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/documents')->group(function (): void {
    Route::get('/', [MaintenanceDocumentController::class, 'index']);
    Route::post('/', [MaintenanceDocumentController::class, 'store']);
    Route::get('/{document}/versions', [MaintenanceDocumentController::class, 'versions']);
    Route::post('/{document}/versions', [MaintenanceDocumentController::class, 'storeVersion']);
    Route::post('/{document}/approve', [MaintenanceDocumentController::class, 'approve']);
    Route::get('/{document}', [MaintenanceDocumentController::class, 'show']);
    Route::patch('/{document}', [MaintenanceDocumentController::class, 'update']);
    Route::delete('/{document}', [MaintenanceDocumentController::class, 'destroy']);
});
