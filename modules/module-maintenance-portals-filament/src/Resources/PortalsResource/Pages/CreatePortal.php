<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portals\Filament\Resources\PortalsResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Portal\Actions\CreatePortalRecord;
use Liberu\Modules\Maintenance\Portals\Filament\Resources\PortalsResource;

final class CreatePortal extends CreateRecord
{
    protected static string $resource = PortalsResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');

        return app(CreatePortalRecord::class)->handle((int) $tenant->getKey(), $data);
    }
}
