<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Reporting\Api\Http\Controllers\ReportingRecordController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/reporting')->group(function (): void {
    Route::get('/', [ReportingRecordController::class, 'index']);
    Route::get('/summary', [ReportingRecordController::class, 'summary']);
    Route::get('/metrics', [ReportingRecordController::class, 'metrics']);
    Route::get('/comprehensive', [ReportingRecordController::class, 'comprehensive']);
    Route::post('/', [ReportingRecordController::class, 'store']);
    Route::get('/{record}', [ReportingRecordController::class, 'show']);
    Route::patch('/{record}', [ReportingRecordController::class, 'update']);
    Route::post('/{record}/publish', [ReportingRecordController::class, 'publish']);
    Route::delete('/{record}', [ReportingRecordController::class, 'destroy']);
});
