<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Filament;

use Filament\Panel;
use Filament\PanelPlugin;
use Liberu\Modules\Maintenance\Compliance\Filament\Resources\ComplianceResource;

class ComplianceFilamentPlugin implements PanelPlugin
{
    public function getId(): string
    {
        return 'module-maintenance-compliance-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ComplianceResource::class]);
    }

    public function boot(Panel $panel): void {}
}
