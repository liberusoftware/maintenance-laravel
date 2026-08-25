<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portals\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PortalsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('module-maintenance-portals::portals-list', Liberu\Modules\Maintenance\Portals\Livewire\Components\PortalsList::class);
    }
}
