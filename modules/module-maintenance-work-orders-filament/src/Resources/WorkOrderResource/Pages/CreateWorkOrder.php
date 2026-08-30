<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder as CreateWorkOrderAction;
use Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource;

class CreateWorkOrder extends CreateRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(CreateWorkOrderAction::class)->handle((int) $teamId, array_merge($data, ['requested_by' => auth()->id()]));
    }
}
