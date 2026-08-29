<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Filament;

use Filament\Panel;
use Filament\PanelPlugin;

class ComplianceFilamentPlugin implements PanelPlugin
{
    public function getId(): string
    {
        return 'module-maintenance-compliance-filament';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
