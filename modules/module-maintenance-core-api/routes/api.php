<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Modules\Maintenance\Core\Api\Http\Controllers\OrganizationController;
use Liberu\Modules\Maintenance\Core\Api\Http\Controllers\PriorityController;
use Liberu\Modules\Maintenance\Core\Api\Http\Controllers\ServiceSettingController;
use Liberu\Modules\Maintenance\Core\Api\Http\Controllers\StatusController;

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
            Route::get('statuses', [StatusController::class, 'index'])->name('statuses.index');
            Route::post('statuses', [StatusController::class, 'store'])->name('statuses.store');
            Route::patch('statuses/{status}', [StatusController::class, 'update'])->name('statuses.update');
            Route::delete('statuses/{status}', [StatusController::class, 'destroy'])->name('statuses.destroy');
            Route::get('priorities', [PriorityController::class, 'index'])->name('priorities.index');
            Route::post('priorities', [PriorityController::class, 'store'])->name('priorities.store');
            Route::patch('priorities/{priority}', [PriorityController::class, 'update'])->name('priorities.update');
            Route::delete('priorities/{priority}', [PriorityController::class, 'destroy'])->name('priorities.destroy');
            Route::get('settings', [ServiceSettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [ServiceSettingController::class, 'store'])->name('settings.store');
        });
    });
