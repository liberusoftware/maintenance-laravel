<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Core\Api\Http\Controllers\OrganizationController;

Route::middleware('api')
    ->prefix('api/v1/maintenance/maintenance-core')
    ->name('api.v1.maintenance.core.')
    ->group(function (): void {
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
            Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
            Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
            Route::patch('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
            Route::delete('organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
        });
    });
