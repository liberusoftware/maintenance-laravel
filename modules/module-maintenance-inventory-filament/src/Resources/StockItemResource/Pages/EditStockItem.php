<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Inventory\Actions\UpdateStockItem as UpdateStockItemAction;
use Liberu\Modules\Maintenance\Inventory\Filament\Resources\StockItemResource;

class EditStockItem extends EditRecord
{
    protected static string $resource = StockItemResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(UpdateStockItemAction::class)->handle((int) $teamId, $record, $data);
    }
}
