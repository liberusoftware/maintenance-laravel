<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Reporting\Filament;

use Filament\Panel;
use Filament\PanelPlugin;
use Liberu\Modules\Maintenance\Report\Filament\Resources\ReportingResource;

class ReportingFilamentPlugin implements PanelPlugin
{
    public function getId(): string
    {
        return 'module-maintenance-reporting-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ReportingResource::class]);
    }

    public function boot(Panel $panel): void {}
}
