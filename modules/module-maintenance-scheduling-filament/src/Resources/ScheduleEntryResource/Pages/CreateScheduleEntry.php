<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource;

class CreateScheduleEntry extends CreateRecord
{
    protected static string $resource = ScheduleEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->currentTeam?->getKey();
        abort_if($data['team_id'] === null, 403);

        return $data;
    }
}
