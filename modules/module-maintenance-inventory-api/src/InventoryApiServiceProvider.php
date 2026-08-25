<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Api;

use Illuminate\Support\ServiceProvider;

class InventoryApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
