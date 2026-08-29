<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Procurement\Actions\UpdatePurchaseRequest as UpdatePurchaseRequestAction;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(UpdatePurchaseRequestAction::class)->handle((int) $teamId, $record, $data);
    }
}
