<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
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
}
