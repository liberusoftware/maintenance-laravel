<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Api;

use Illuminate\Support\ServiceProvider;

class SchedulingApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
