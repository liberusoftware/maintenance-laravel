<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource;

class CreateInspection extends CreateRecord
{
    protected static string $resource = InspectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->currentTeam?->getKey();
        $data['inspector_id'] = auth()->id();
        abort_if($data['team_id'] === null, 403);

        return $data;
    }
}
