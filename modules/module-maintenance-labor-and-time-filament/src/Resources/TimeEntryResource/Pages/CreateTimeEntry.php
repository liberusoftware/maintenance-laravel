<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource;

class CreateTimeEntry extends CreateRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->currentTeam?->getKey();
        $data['user_id'] = auth()->id();
        abort_if($data['team_id'] === null, 403);

        return $data;
    }
}
