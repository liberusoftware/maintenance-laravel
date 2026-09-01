<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\LaborAndTime\Api\Http\Controllers\LaborRecordController;
use Liberu\Modules\Maintenance\LaborAndTime\Api\Http\Controllers\TimeEntryController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/labor-and-time')->group(function (): void {
    Route::get('/', [TimeEntryController::class, 'index']);
    Route::post('/', [TimeEntryController::class, 'store']);
    Route::match(['get', 'post'], '/skills', [LaborRecordController::class, 'skills']);
    Route::match(['get', 'post'], '/attendance', [LaborRecordController::class, 'attendance']);
    Route::match(['get', 'post'], '/rates', [LaborRecordController::class, 'rates']);
    Route::match(['get', 'post'], '/expenses', [LaborRecordController::class, 'expenses']);
    Route::get('/{timeEntry}', [TimeEntryController::class, 'show']);
    Route::patch('/{timeEntry}', [TimeEntryController::class, 'update']);
    Route::delete('/{timeEntry}', [TimeEntryController::class, 'destroy']);
    Route::post('/{timeEntry}/approve', [TimeEntryController::class, 'approve']);
    Route::post('/{timeEntry}/reject', [TimeEntryController::class, 'reject']);
});
