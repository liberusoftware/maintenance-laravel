<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource;

class CreateStockItem extends CreateRecord
{
    protected static string $resource = StockItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->currentTeam?->getKey();
        abort_if($data['team_id'] === null, 403);

        return $data;
    }
}
