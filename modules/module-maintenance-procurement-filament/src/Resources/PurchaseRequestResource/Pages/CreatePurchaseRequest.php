<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Procurement\Actions\CreatePurchaseRequest as CreatePurchaseRequestAction;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource;

class CreatePurchaseRequest extends CreateRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(CreatePurchaseRequestAction::class)->handle((int) $teamId, array_merge($data, ['requested_by' => auth()->id()]));
    }
}
