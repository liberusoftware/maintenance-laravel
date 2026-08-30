<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Models\Territory;
use Liberu\Modules\Maintenance\Scheduling\Policies\ScheduleEntryPolicy;
use Liberu\Modules\Maintenance\Scheduling\Policies\TerritoryPolicy;

class SchedulingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(ScheduleEntry::class, ScheduleEntryPolicy::class);
        Gate::policy(Territory::class, TerritoryPolicy::class);
    }
}
