<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateScheduleEntry as CreateScheduleEntryAction;
use Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource;

class CreateScheduleEntry extends CreateRecord
{
    protected static string $resource = ScheduleEntryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(CreateScheduleEntryAction::class)->handle((int) $teamId, $data);
    }
}
