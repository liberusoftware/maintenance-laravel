<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Core\Models\Organization;
use Liberu\Modules\Maintenance\Core\Policies\OrganizationPolicy;

final class MaintenanceCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/maintenance-core.php', 'maintenance-core');
    }

    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([
            __DIR__.'/../config/maintenance-core.php' => config_path('maintenance-core.php'),
        ], 'maintenance-core-config');
    }
}
