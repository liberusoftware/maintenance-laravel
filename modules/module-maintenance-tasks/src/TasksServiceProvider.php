<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Tasks;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Tasks\Models\MaintenanceTask;
use Liberu\Modules\Maintenance\Tasks\Policies\MaintenanceTaskPolicy;

final class TasksServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(MaintenanceTask::class, MaintenanceTaskPolicy::class);
    }
}
