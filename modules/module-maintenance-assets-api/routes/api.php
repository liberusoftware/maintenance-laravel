<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Assets\Api\Http\Controllers\AssetController;
use Liberu\Modules\Maintenance\Assets\Api\Http\Controllers\SensorController;

Route::prefix('api/iot-sensors')->group(function (): void {
    Route::post('/readings', [SensorController::class, 'store']);
    Route::post('/readings/batch', [SensorController::class, 'batch']);
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/dashboard', [SensorController::class, 'dashboard']);
        Route::get('/assets/{asset}/health', [SensorController::class, 'health']);
        Route::get('/assets/{asset}/insights', [SensorController::class, 'insights']);
    });
});

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/assets')->group(function (): void {
    Route::get('/', [AssetController::class, 'index']);
    Route::post('/', [AssetController::class, 'store']);
    Route::get('/categories', [AssetController::class, 'categories']);
    Route::post('/categories', [AssetController::class, 'storeCategory']);
    Route::post('/{asset}/history', [AssetController::class, 'history']);
    Route::get('/{asset}/history', [AssetController::class, 'assetHistory']);
    Route::get('/{asset}/specifications', [AssetController::class, 'specifications']);
    Route::post('/{asset}/specifications', [AssetController::class, 'storeSpecification']);
    Route::get('/{asset}/warranties', [AssetController::class, 'warranties']);
    Route::post('/{asset}/warranties', [AssetController::class, 'storeWarranty']);
    Route::get('/{asset}/meters', [AssetController::class, 'meters']);
    Route::post('/{asset}/meters', [AssetController::class, 'storeMeter']);
    Route::post('/{asset}/meters/{meter}/readings', [AssetController::class, 'storeMeterReading']);
    Route::get('/{asset}', [AssetController::class, 'show']);
    Route::patch('/{asset}', [AssetController::class, 'update']);
    Route::delete('/{asset}', [AssetController::class, 'destroy']);
});
