<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Inventory\Api\Http\Controllers\InventoryLocationController;
use Liberu\Modules\Maintenance\Inventory\Api\Http\Controllers\StockItemController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/inventory')->group(function (): void {
    Route::get('/', [StockItemController::class, 'index']);
    Route::get('/locations', [InventoryLocationController::class, 'index']);
    Route::post('/locations', [InventoryLocationController::class, 'store']);
    Route::post('/locations/{location}/levels', [InventoryLocationController::class, 'setLevel']);
    Route::post('/transfers', [InventoryLocationController::class, 'transfer']);
    Route::get('/reorder-recommendations', [StockItemController::class, 'reorderRecommendations']);
    Route::post('/', [StockItemController::class, 'store']);
    Route::get('/{stockItem}', [StockItemController::class, 'show']);
    Route::patch('/{stockItem}', [StockItemController::class, 'update']);
    Route::delete('/{stockItem}', [StockItemController::class, 'destroy']);
    Route::post('/{stockItem}/adjust', [StockItemController::class, 'adjust']);
    Route::post('/{stockItem}/reserve', [StockItemController::class, 'reserve']);
    Route::post('/{stockItem}/release', [StockItemController::class, 'release']);
    Route::post('/{stockItem}/issue', [StockItemController::class, 'issue']);
    Route::post('/{stockItem}/return', [StockItemController::class, 'return']);
    Route::post('/{stockItem}/count', [StockItemController::class, 'count']);
    Route::get('/{stockItem}/movements', [StockItemController::class, 'movements']);
});
