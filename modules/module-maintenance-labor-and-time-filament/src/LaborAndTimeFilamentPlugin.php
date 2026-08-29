<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource;

class LaborAndTimeFilamentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'maintenance-labor-and-time';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([TimeEntryResource::class]);
    }

    public function boot(Panel $panel): void {}
}
