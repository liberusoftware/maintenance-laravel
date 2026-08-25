<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CustomersAndSitesLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-customers-and-sites-livewire');
        Livewire::addNamespace('module-maintenance-customers-and-sites', __NAMESPACE__.'\\Components', __DIR__.'/Components', __DIR__.'/../resources/views/livewire');
    }
}
