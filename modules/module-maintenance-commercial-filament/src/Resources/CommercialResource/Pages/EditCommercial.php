<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Filament\Resources\CommercialResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Commercial\Actions\UpdateCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Filament\Resources\CommercialResource;

final class EditCommercial extends EditRecord
{
    protected static string $resource = CommercialResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');

        return app(UpdateCommercialRecord::class)->handle((int) $tenant->getKey(), $record, $data);
    }
}
