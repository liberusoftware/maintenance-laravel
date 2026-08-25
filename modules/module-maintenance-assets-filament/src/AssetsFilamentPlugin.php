<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource;

class AssetsFilamentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'maintenance-assets';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AssetResource::class]);
    }

    public function boot(Panel $panel): void {}
}
