<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Policies\TimeEntryPolicy;

class LaborAndTimeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(TimeEntry::class, TimeEntryPolicy::class);
    }
}
