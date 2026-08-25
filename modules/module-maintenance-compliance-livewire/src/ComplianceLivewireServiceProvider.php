<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ComplianceLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('module-maintenance-compliance::compliance-list', Liberu\Modules\Maintenance\Compliance\Livewire\Components\ComplianceList::class);
    }
}
