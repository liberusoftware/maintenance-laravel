<?php
use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\CustomersAndSites\Api\Http\Controllers\CustomersAndSitesController;
Route::middleware('auth:sanctum')->prefix('api/v1/maintenance/customers-and-sites')->group(function (): void { Route::get('/',[CustomersAndSitesController::class,'index']); Route::post('/',[CustomersAndSitesController::class,'store']); Route::get('/{customer}',[CustomersAndSitesController::class,'show']); });
