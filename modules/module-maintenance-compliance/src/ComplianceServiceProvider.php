<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance;

use Illuminate\Support\ServiceProvider;

class ComplianceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
