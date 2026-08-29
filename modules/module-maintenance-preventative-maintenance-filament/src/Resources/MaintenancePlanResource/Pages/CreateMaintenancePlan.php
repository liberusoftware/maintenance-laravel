<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CreateMaintenancePlan as CreateMaintenancePlanAction;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource;

class CreateMaintenancePlan extends CreateRecord
{
    protected static string $resource = MaintenancePlanResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(CreateMaintenancePlanAction::class)->handle((int) $teamId, $data);
    }
}
