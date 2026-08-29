<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portal\Filament\Resources\PortalsResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Portal\Actions\UpdatePortalRecord;
use Liberu\Modules\Maintenance\Portal\Filament\Resources\PortalsResource;

final class EditPortal extends EditRecord
{
    protected static string $resource = PortalsResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');

        return app(UpdatePortalRecord::class)->handle((int) $tenant->getKey(), $record, $data);
    }
}
