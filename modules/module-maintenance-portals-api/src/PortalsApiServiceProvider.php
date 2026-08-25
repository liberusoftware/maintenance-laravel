<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portals\Api;

use Illuminate\Support\ServiceProvider;

class PortalsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
