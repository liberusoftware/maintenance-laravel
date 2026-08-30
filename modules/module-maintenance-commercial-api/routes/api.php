<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Commercial\Api\Http\Controllers\CommercialRecordController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/commercial')->group(function (): void {
    Route::get('/', [CommercialRecordController::class, 'index']);
    Route::post('/', [CommercialRecordController::class, 'store']);
    Route::get('/{record}/lines', [CommercialRecordController::class, 'lines']);
    Route::get('/{record}/coverages', [CommercialRecordController::class, 'coverages']);
    Route::post('/{record}/coverages', [CommercialRecordController::class, 'storeCoverage']);
    Route::post('/{record}/lines', [CommercialRecordController::class, 'storeLine']);
    Route::patch('/{record}/lines/{line}', [CommercialRecordController::class, 'updateLine']);
    Route::delete('/{record}/lines/{line}', [CommercialRecordController::class, 'destroyLine']);
    Route::post('/{record}/transition', [CommercialRecordController::class, 'transition']);
    Route::get('/{record}', [CommercialRecordController::class, 'show']);
    Route::patch('/{record}', [CommercialRecordController::class, 'update']);
    Route::delete('/{record}', [CommercialRecordController::class, 'destroy']);
});
