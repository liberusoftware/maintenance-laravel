<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Scheduling\Actions\UpdateScheduleEntry as UpdateScheduleEntryAction;
use Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource;

class EditScheduleEntry extends EditRecord
{
    protected static string $resource = ScheduleEntryResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(UpdateScheduleEntryAction::class)->handle((int) $teamId, $record, $data);
    }
}
