<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AssetsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-assets-livewire');
        Livewire::addNamespace('module-maintenance-assets', __NAMESPACE__.'\\Components', __DIR__.'/Components', __DIR__.'/../resources/views/livewire');
    }
}
