<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial;

use Illuminate\Support\ServiceProvider;

class CommercialServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
