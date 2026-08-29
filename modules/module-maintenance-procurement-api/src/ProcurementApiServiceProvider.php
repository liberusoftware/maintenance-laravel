<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Api;

use Illuminate\Support\ServiceProvider;

class ProcurementApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
