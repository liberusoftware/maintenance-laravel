<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource;

class InventoryFilamentPlugin implements Plugin
{
    public function getId(): string
    {
        return 'maintenance-inventory';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([StockItemResource::class]);
    }

    public function boot(Panel $panel): void {}
}
