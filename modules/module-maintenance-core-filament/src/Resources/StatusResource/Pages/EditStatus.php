<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\StatusResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Core\Actions\UpdateStatus as UpdateStatusAction;
use Liberu\Modules\Maintenance\Core\Filament\Resources\StatusResource;

final class EditStatus extends EditRecord
{
    protected static string $resource = StatusResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['team_id']);

        return app(UpdateStatusAction::class)->execute($record, $data);
    }
}
