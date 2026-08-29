<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\LaborAndTime\Api\Http\Controllers\TimeEntryController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/labor-and-time')->group(function (): void {
    Route::get('/', [TimeEntryController::class, 'index']);
    Route::post('/', [TimeEntryController::class, 'store']);
    Route::get('/{timeEntry}', [TimeEntryController::class, 'show']);
    Route::patch('/{timeEntry}', [TimeEntryController::class, 'update']);
    Route::delete('/{timeEntry}', [TimeEntryController::class, 'destroy']);
    Route::post('/{timeEntry}/approve', [TimeEntryController::class, 'approve']);
});
