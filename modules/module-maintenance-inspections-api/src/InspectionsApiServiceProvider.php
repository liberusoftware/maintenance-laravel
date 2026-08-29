<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Api;

use Illuminate\Support\ServiceProvider;

class InspectionsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
