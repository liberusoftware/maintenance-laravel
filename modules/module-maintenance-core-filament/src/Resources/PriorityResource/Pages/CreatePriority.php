<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\PriorityResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Core\Actions\CreatePriority as CreatePriorityAction;
use Liberu\Modules\Maintenance\Core\Filament\Resources\PriorityResource;

final class CreatePriority extends CreateRecord
{
    protected static string $resource = PriorityResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');

        return app(CreatePriorityAction::class)->execute((int) $tenant->getKey(), $data);
    }
}
