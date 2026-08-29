<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Inspections\Api\Http\Controllers\InspectionController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/inspections')->group(function (): void {
    Route::get('/', [InspectionController::class, 'index']);
    Route::post('/', [InspectionController::class, 'store']);
    Route::get('/{inspection}', [InspectionController::class, 'show']);
    Route::patch('/{inspection}', [InspectionController::class, 'update']);
    Route::post('/{inspection}/complete', [InspectionController::class, 'complete']);
    Route::delete('/{inspection}', [InspectionController::class, 'destroy']);
});
