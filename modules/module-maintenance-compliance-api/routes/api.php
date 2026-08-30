<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Compliance\Api\Http\Controllers\ComplianceOperationsController;
use Liberu\Modules\Maintenance\Compliance\Api\Http\Controllers\ComplianceRecordController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/compliance')->group(function (): void {
    Route::get('/requirements', [ComplianceOperationsController::class, 'requirements']);
    Route::post('/requirements', [ComplianceOperationsController::class, 'storeRequirement']);
    Route::get('/permits', [ComplianceOperationsController::class, 'permits']);
    Route::post('/permits', [ComplianceOperationsController::class, 'storePermit']);
    Route::get('/risk-assessments', [ComplianceOperationsController::class, 'riskAssessments']);
    Route::post('/risk-assessments', [ComplianceOperationsController::class, 'storeRiskAssessment']);
    Route::get('/incidents', [ComplianceOperationsController::class, 'incidents']);
    Route::post('/incidents', [ComplianceOperationsController::class, 'storeIncident']);
    Route::get('/', [ComplianceRecordController::class, 'index']);
    Route::post('/', [ComplianceRecordController::class, 'store']);
    Route::get('/{record}/evidence', [ComplianceRecordController::class, 'evidence']);
    Route::post('/{record}/evidence', [ComplianceRecordController::class, 'storeEvidence']);
    Route::get('/{record}/corrective-actions', [ComplianceRecordController::class, 'correctiveActions']);
    Route::post('/{record}/corrective-actions', [ComplianceRecordController::class, 'storeCorrectiveAction']);
    Route::post('/{record}/corrective-actions/{action}/complete', [ComplianceRecordController::class, 'completeCorrectiveAction']);
    Route::get('/{record}', [ComplianceRecordController::class, 'show']);
    Route::patch('/{record}', [ComplianceRecordController::class, 'update']);
    Route::delete('/{record}', [ComplianceRecordController::class, 'destroy']);
});
