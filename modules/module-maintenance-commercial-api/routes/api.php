<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Commercial\Api\Http\Controllers\CommercialRecordController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/commercial')->group(function (): void {
    Route::get('/', [CommercialRecordController::class, 'index']);
    Route::post('/', [CommercialRecordController::class, 'store']);
    Route::get('/{record}', [CommercialRecordController::class, 'show']);
    Route::patch('/{record}', [CommercialRecordController::class, 'update']);
    Route::delete('/{record}', [CommercialRecordController::class, 'destroy']);
});
