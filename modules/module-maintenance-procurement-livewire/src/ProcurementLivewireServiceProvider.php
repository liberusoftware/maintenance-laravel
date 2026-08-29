<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ProcurementLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-procurement-livewire');
        Livewire::addNamespace('module-maintenance-procurement', __NAMESPACE__.'\\Components', __DIR__.'/Components', __DIR__.'/../resources/views/livewire');
    }
}
