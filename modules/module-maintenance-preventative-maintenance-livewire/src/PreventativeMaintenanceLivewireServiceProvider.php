<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PreventativeMaintenanceLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-preventative-maintenance-livewire');
        Livewire::addNamespace('module-maintenance-preventative-maintenance', __NAMESPACE__.'\\Components', __DIR__.'/Components', __DIR__.'/../resources/views/livewire');
    }
}
