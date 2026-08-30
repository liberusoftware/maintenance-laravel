<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portals\Filament;

use Filament\Panel;
use Filament\PanelPlugin;
use Liberu\Modules\Maintenance\Portal\Filament\Resources\PortalsResource;

class PortalsFilamentPlugin implements PanelPlugin
{
    public function getId(): string
    {
        return 'module-maintenance-portals-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PortalsResource::class]);
    }

    public function boot(Panel $panel): void {}
}
