<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Filament;

use Filament\Panel;
use Filament\PanelPlugin;
use Liberu\Modules\Maintenance\Commercial\Filament\Resources\CommercialResource;

class CommercialFilamentPlugin implements PanelPlugin
{
    public function getId(): string
    {
        return 'module-maintenance-commercial-filament';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([CommercialResource::class]);
    }

    public function boot(Panel $panel): void {}
}
