<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portal;

use Illuminate\Support\ServiceProvider;

class PortalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
