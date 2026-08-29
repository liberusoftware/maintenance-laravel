<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Compliance\Api\Http\Controllers\ComplianceRecordController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/compliance')->group(function (): void {
    Route::get('/', [ComplianceRecordController::class, 'index']);
    Route::post('/', [ComplianceRecordController::class, 'store']);
    Route::get('/{record}', [ComplianceRecordController::class, 'show']);
});
