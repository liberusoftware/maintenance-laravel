<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CommercialLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('module-maintenance-commercial::commercial-list', Liberu\Modules\Maintenance\Commercial\Livewire\Components\CommercialList::class);
    }
}
