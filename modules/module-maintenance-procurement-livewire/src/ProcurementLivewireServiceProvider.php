<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Modules\Maintenance\Procurement\Livewire\Components\VendorContractList;
use Liberu\Modules\Maintenance\Procurement\Livewire\Components\VendorEvaluationList;
use Livewire\Livewire;

class ProcurementLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-maintenance-procurement-livewire');
        Livewire::addNamespace('module-maintenance-procurement', __NAMESPACE__.'\\Components', __DIR__.'/Components', __DIR__.'/../resources/views/livewire');
        Livewire::component('module-maintenance-procurement::vendor-contract-list', VendorContractList::class);
        Livewire::component('module-maintenance-procurement::vendor-evaluation-list', VendorEvaluationList::class);
    }
}
