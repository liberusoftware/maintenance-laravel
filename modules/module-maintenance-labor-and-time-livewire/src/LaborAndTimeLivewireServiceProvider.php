<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LaborAndTimeLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-labor-and-time-livewire');
        Livewire::addNamespace('module-maintenance-labor-and-time', __NAMESPACE__.'\\Components', __DIR__.'/Components', __DIR__.'/../resources/views/livewire');
    }
}
