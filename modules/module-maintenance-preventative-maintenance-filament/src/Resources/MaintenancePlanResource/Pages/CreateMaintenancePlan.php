<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Filament\Resources\MaintenancePlanResource;

class CreateMaintenancePlan extends CreateRecord
{
    protected static string $resource = MaintenancePlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->currentTeam?->getKey();
        abort_if($data['team_id'] === null, 403);

        return $data;
    }
}
