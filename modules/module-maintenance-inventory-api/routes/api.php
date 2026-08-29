<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Inventory\Api\Http\Controllers\StockItemController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/inventory')->group(function (): void {
    Route::get('/', [StockItemController::class, 'index']);
    Route::post('/', [StockItemController::class, 'store']);
    Route::get('/{stockItem}', [StockItemController::class, 'show']);
    Route::post('/{stockItem}/adjust', [StockItemController::class, 'adjust']);
});
