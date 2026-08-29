<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateTimeEntry as CreateTimeEntryAction;
use Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource;

class CreateTimeEntry extends CreateRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(CreateTimeEntryAction::class)->handle((int) $teamId, array_merge($data, ['user_id' => auth()->id()]));
    }
}
