<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateStockItem as CreateStockItemAction;
use Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource;

class CreateStockItem extends CreateRecord
{
    protected static string $resource = StockItemResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(CreateStockItemAction::class)->handle((int) $teamId, $data);
    }
}
