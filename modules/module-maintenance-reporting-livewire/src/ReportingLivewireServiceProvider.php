<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Reporting\Livewire;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ReportingLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::component('module-maintenance-reporting::reporting-list', Liberu\Modules\Maintenance\Reporting\Livewire\Components\ReportingList::class);
    }
}
