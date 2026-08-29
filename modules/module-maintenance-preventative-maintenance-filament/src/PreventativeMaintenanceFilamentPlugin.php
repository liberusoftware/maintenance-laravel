<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource;

class PreventativeMaintenanceFilamentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'maintenance-preventative-maintenance';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([MaintenancePlanResource::class]);
    }

    public function boot(Panel $panel): void {}
}
