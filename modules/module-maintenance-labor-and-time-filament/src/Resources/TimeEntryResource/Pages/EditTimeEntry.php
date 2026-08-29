<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\UpdateTimeEntry as UpdateTimeEntryAction;
use Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource;

class EditTimeEntry extends EditRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(UpdateTimeEntryAction::class)->handle((int) $teamId, $record, $data);
    }
}
