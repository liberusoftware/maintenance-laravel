<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Core\Actions\CreateOrganization as CreateOrganizationAction;
use Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource;

final class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');
        $data['team_id'] = $tenant->getKey();

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateOrganizationAction::class)->execute((int) $data['team_id'], (string) $data['name'], (string) $data['code'], $data['description'] ?? null);
    }
}
