<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\UpdateMaintenancePlan as UpdateMaintenancePlanAction;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource;

class EditMaintenancePlan extends EditRecord
{
    protected static string $resource = MaintenancePlanResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(UpdateMaintenancePlanAction::class)->handle((int) $teamId, $record, $data);
    }
}
