<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Api;

use Illuminate\Support\ServiceProvider;

class WorkOrdersApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
