<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class InventoryLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-inventory-livewire');
        Livewire::addNamespace('module-maintenance-inventory', __NAMESPACE__.'\\Components', __DIR__.'/Components', __DIR__.'/../resources/views/livewire');
    }
}
