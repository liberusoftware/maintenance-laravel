<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Inspections\Actions\UpdateInspection as UpdateInspectionAction;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource;

final class EditInspection extends EditRecord
{
    protected static string $resource = InspectionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(UpdateInspectionAction::class)->handle((int) $teamId, $record, $data);
    }
}
