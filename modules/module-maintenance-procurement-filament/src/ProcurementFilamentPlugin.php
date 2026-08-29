<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource;

class ProcurementFilamentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'maintenance-procurement';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PurchaseRequestResource::class]);
        $panel->resources([VendorContractResource::class]);
    }

    public function boot(Panel $panel): void {}
}
