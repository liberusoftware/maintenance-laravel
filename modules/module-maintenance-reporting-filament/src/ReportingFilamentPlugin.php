<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Reporting\Filament;

use Filament\Panel;
use Filament\PanelPlugin;

class ReportingFilamentPlugin implements PanelPlugin
{
    public function getId(): string
    {
        return 'module-maintenance-reporting-filament';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
