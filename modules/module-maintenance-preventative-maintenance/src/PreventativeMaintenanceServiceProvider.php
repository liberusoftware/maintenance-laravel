<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Console\CheckDueMaintenanceCommand;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Console\SendMaintenanceRemindersCommand;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Policies\MaintenancePlanPolicy;

class PreventativeMaintenanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(MaintenancePlan::class, MaintenancePlanPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckDueMaintenanceCommand::class,
                SendMaintenanceRemindersCommand::class,
            ]);
        }
    }
}
