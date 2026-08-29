<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class InspectionsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-inspections-livewire');
        Livewire::addNamespace('module-maintenance-inspections', __NAMESPACE__.'\\Components', __DIR__.'/Components', __DIR__.'/../resources/views/livewire');
    }
}
