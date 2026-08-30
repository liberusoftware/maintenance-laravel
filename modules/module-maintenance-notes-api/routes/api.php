<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Notes\Api\Http\Controllers\MaintenanceNoteController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/notes')->group(function (): void {
    Route::get('/', [MaintenanceNoteController::class, 'index']);
    Route::post('/', [MaintenanceNoteController::class, 'store']);
    Route::get('/{note}', [MaintenanceNoteController::class, 'show']);
    Route::patch('/{note}', [MaintenanceNoteController::class, 'update']);
    Route::delete('/{note}', [MaintenanceNoteController::class, 'destroy']);
});
