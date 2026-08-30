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
    Route::get('/contacts', [CustomersAndSitesController::class, 'contacts']);
    Route::post('/contacts', [CustomersAndSitesController::class, 'storeContact']);
    Route::patch('/contacts/{contact}', [CustomersAndSitesController::class, 'updateContact']);
    Route::delete('/contacts/{contact}', [CustomersAndSitesController::class, 'destroyContact']);
    Route::get('/locations', [CustomersAndSitesController::class, 'locations']);
    Route::post('/locations', [CustomersAndSitesController::class, 'storeLocation']);
    Route::patch('/locations/{location}', [CustomersAndSitesController::class, 'updateLocation']);
    Route::delete('/locations/{location}', [CustomersAndSitesController::class, 'destroyLocation']);
    Route::get('/service-windows', [CustomersAndSitesController::class, 'serviceWindows']);
    Route::post('/service-windows', [CustomersAndSitesController::class, 'storeServiceWindow']);
    Route::patch('/service-windows/{serviceWindow}', [CustomersAndSitesController::class, 'updateServiceWindow']);
    Route::delete('/service-windows/{serviceWindow}', [CustomersAndSitesController::class, 'destroyServiceWindow']);
    Route::get('/{customer}', [CustomersAndSitesController::class, 'show']);
    Route::patch('/{customer}', [CustomersAndSitesController::class, 'update']);
    Route::delete('/{customer}', [CustomersAndSitesController::class, 'destroy']);
});
