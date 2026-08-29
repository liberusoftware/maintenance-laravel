<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\StatusResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Core\Actions\CreateStatus as CreateStatusAction;
use Liberu\Modules\Maintenance\Core\Filament\Resources\StatusResource;

final class CreateStatus extends CreateRecord
{
    protected static string $resource = StatusResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');

        return app(CreateStatusAction::class)->execute((int) $tenant->getKey(), $data);
    }
}
