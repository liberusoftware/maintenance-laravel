<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource;

class ListStockItems extends ListRecords
{
    protected static string $resource = StockItemResource::class;
}
