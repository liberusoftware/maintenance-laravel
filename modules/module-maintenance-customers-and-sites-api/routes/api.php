<?php

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\CustomersAndSites\Api\Http\Controllers\CustomersAndSitesController;

Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/customers-and-sites')->group(function (): void {
    Route::get('/', [CustomersAndSitesController::class, 'index']);
    Route::post('/', [CustomersAndSitesController::class, 'store']);
    Route::get('/sites', [CustomersAndSitesController::class, 'sites']);
    Route::post('/sites', [CustomersAndSitesController::class, 'storeSite']);
    Route::patch('/sites/{site}', [CustomersAndSitesController::class, 'updateSite']);
    Route::delete('/sites/{site}', [CustomersAndSitesController::class, 'destroySite']);
    Route::get('/{customer}', [CustomersAndSitesController::class, 'show']);
    Route::patch('/{customer}', [CustomersAndSitesController::class, 'update']);
    Route::delete('/{customer}', [CustomersAndSitesController::class, 'destroy']);
});
