<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource;

class SchedulingFilamentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'maintenance-scheduling';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ScheduleEntryResource::class]);
    }

    public function boot(Panel $panel): void {}
}
