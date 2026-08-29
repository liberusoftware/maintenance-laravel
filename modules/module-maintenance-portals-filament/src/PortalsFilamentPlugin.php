<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portals\Filament;

use Filament\Panel;
use Filament\PanelPlugin;

class PortalsFilamentPlugin implements PanelPlugin
{
    public function getId(): string
    {
        return 'module-maintenance-portals-filament';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
