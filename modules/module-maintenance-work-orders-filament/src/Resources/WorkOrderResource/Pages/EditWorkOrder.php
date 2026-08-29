<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\WorkOrders\Actions\UpdateWorkOrder as UpdateWorkOrderAction;
use Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource;

class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(UpdateWorkOrderAction::class)->handle((int) $teamId, $record, $data);
    }
}
