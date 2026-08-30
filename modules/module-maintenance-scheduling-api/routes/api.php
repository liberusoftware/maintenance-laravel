<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Scheduling\Api\Http\Controllers\AvailabilityWindowController;
use Liberu\Modules\Maintenance\Scheduling\Api\Http\Controllers\ScheduleEntryController;
use Liberu\Modules\Maintenance\Scheduling\Api\Http\Controllers\SchedulingOperationsController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/scheduling')->group(function (): void {
    Route::get('/', [ScheduleEntryController::class, 'index']);
    Route::post('/', [ScheduleEntryController::class, 'store']);
    Route::get('/availability', [AvailabilityWindowController::class, 'index']);
    Route::post('/availability', [AvailabilityWindowController::class, 'store']);
    Route::get('/skills', [SchedulingOperationsController::class, 'skills']);
    Route::post('/skills', [SchedulingOperationsController::class, 'storeSkill']);
    Route::get('/shifts', [SchedulingOperationsController::class, 'shifts']);
    Route::post('/shifts', [SchedulingOperationsController::class, 'storeShift']);
    Route::get('/{scheduleEntry}/travel', [SchedulingOperationsController::class, 'travelIndex']);
    Route::post('/{scheduleEntry}/travel', [SchedulingOperationsController::class, 'travel']);
    Route::get('/{scheduleEntry}/dispatches', [SchedulingOperationsController::class, 'dispatchIndex']);
    Route::post('/{scheduleEntry}/dispatches', [SchedulingOperationsController::class, 'dispatch']);
    Route::get('/{scheduleEntry}', [ScheduleEntryController::class, 'show']);
    Route::patch('/{scheduleEntry}', [ScheduleEntryController::class, 'update']);
    Route::post('/{scheduleEntry}/transitions', [ScheduleEntryController::class, 'transition']);
    Route::delete('/{scheduleEntry}', [ScheduleEntryController::class, 'destroy']);
});
