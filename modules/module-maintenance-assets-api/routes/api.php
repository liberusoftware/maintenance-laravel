<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Assets\Api\Http\Controllers\AssetController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/assets')->group(function (): void {
    Route::get('/', [AssetController::class, 'index']);
    Route::post('/', [AssetController::class, 'store']);
    Route::get('/{asset}', [AssetController::class, 'show']);
});
