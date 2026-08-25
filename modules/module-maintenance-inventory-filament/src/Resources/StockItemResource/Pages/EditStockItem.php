<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource;

class EditStockItem extends EditRecord
{
    protected static string $resource = StockItemResource::class;
}
