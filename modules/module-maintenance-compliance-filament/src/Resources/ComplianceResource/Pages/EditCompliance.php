<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Filament\Resources\ComplianceResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Compliance\Actions\UpdateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Filament\Resources\ComplianceResource;

final class EditCompliance extends EditRecord
{
    protected static string $resource = ComplianceResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');

        return app(UpdateComplianceRecord::class)->handle((int) $tenant->getKey(), $record, $data);
    }
}
