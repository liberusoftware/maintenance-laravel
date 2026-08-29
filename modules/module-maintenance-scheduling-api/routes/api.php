<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Scheduling\Api\Http\Controllers\ScheduleEntryController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/scheduling')->group(function (): void {
    Route::get('/', [ScheduleEntryController::class, 'index']);
    Route::post('/', [ScheduleEntryController::class, 'store']);
    Route::get('/{scheduleEntry}', [ScheduleEntryController::class, 'show']);
    Route::patch('/{scheduleEntry}', [ScheduleEntryController::class, 'update']);
    Route::post('/{scheduleEntry}/transitions', [ScheduleEntryController::class, 'transition']);
    Route::delete('/{scheduleEntry}', [ScheduleEntryController::class, 'destroy']);
});
