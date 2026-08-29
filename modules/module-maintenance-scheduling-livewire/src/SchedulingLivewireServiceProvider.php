<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SchedulingLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-scheduling-livewire');
        Livewire::addNamespace('module-maintenance-scheduling', __NAMESPACE__.'\\Components', __DIR__.'/Components', __DIR__.'/../resources/views/livewire');
    }
}
