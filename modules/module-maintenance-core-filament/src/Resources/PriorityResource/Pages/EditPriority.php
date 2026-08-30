<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\PriorityResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Core\Actions\UpdatePriority as UpdatePriorityAction;
use Liberu\Modules\Maintenance\Core\Filament\Resources\PriorityResource;

final class EditPriority extends EditRecord
{
    protected static string $resource = PriorityResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['team_id']);

        return app(UpdatePriorityAction::class)->execute($record, $data);
    }
}
