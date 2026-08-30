<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Inspections\Api\Http\Controllers\InspectionController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/inspections')->group(function (): void {
    Route::get('/templates', [InspectionController::class, 'templates']);
    Route::post('/templates', [InspectionController::class, 'storeTemplate']);
    Route::post('/templates/{template}/items', [InspectionController::class, 'storeTemplateItem']);
    Route::patch('/templates/{template}/items/{item}', [InspectionController::class, 'updateTemplateItem']);
    Route::delete('/templates/{template}/items/{item}', [InspectionController::class, 'destroyTemplateItem']);
    Route::post('/templates/{template}/duplicate', [InspectionController::class, 'duplicateTemplate']);
    Route::get('/', [InspectionController::class, 'index']);
    Route::post('/', [InspectionController::class, 'store']);
    Route::get('/{inspection}', [InspectionController::class, 'show']);
    Route::patch('/{inspection}', [InspectionController::class, 'update']);
    Route::post('/{inspection}/complete', [InspectionController::class, 'complete']);
    Route::get('/{inspection}/follow-ups', [InspectionController::class, 'followUps']);
    Route::post('/{inspection}/follow-ups', [InspectionController::class, 'storeFollowUp']);
    Route::post('/follow-ups/{followUp}/complete', [InspectionController::class, 'completeFollowUp']);
    Route::delete('/{inspection}', [InspectionController::class, 'destroy']);
});
