<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Core\Actions\UpdateOrganization as UpdateOrganizationAction;
use Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource;

final class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['team_id']);

        return app(UpdateOrganizationAction::class)->execute($record, $data);
    }
}
