<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Filament;

use Filament\Panel;
use Filament\PanelPlugin;

class CommercialFilamentPlugin implements PanelPlugin
{
    public function getId(): string
    {
        return 'module-maintenance-commercial-filament';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
