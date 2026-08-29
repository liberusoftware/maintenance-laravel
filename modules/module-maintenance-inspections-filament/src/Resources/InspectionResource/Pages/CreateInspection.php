<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspection as CreateInspectionAction;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource;

class CreateInspection extends CreateRecord
{
    protected static string $resource = InspectionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(CreateInspectionAction::class)->handle((int) $teamId, array_merge($data, ['inspector_id' => auth()->id()]));
    }
}
