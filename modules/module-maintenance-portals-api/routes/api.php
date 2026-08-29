<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Portals\Api\Http\Controllers\PortalsRecordController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/portals')->group(function (): void {
    Route::get('/', [PortalsRecordController::class, 'index']);
    Route::post('/', [PortalsRecordController::class, 'store']);
    Route::get('/{record}', [PortalsRecordController::class, 'show']);
    Route::patch('/{record}', [PortalsRecordController::class, 'update']);
    Route::delete('/{record}', [PortalsRecordController::class, 'destroy']);
});
