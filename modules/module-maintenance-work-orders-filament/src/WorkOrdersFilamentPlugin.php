<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource;

class WorkOrdersFilamentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'maintenance-work-orders';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([WorkOrderResource::class]);
    }

    public function boot(Panel $panel): void {}
}
