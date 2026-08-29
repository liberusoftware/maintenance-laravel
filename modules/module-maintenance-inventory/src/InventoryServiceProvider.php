<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;
use Liberu\Modules\Maintenance\Inventory\Policies\StockItemPolicy;

class InventoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(StockItem::class, StockItemPolicy::class);
    }
}
