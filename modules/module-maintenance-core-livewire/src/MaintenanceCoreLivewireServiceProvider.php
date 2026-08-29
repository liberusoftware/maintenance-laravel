<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class MaintenanceCoreLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-core-livewire');
        Livewire::addNamespace(
            'module-maintenance-core',
            classNamespace: 'Liberu\\Modules\\Maintenance\\Core\\Livewire\\Components',
            classPath: __DIR__.'/Components',
            classViewPath: __DIR__.'/../resources/views/livewire',
        );
    }
}
