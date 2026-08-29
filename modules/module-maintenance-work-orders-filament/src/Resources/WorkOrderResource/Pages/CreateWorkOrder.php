<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource;

class CreateWorkOrder extends CreateRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->currentTeam?->getKey();
        $data['requested_by'] = auth()->id();
        abort_if($data['team_id'] === null, 403);

        return $data;
    }
}
