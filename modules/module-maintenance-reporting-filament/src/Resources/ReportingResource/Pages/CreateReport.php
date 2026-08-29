<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Filament\Resources\ReportingResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Report\Actions\CreateReportRecord;
use Liberu\Modules\Maintenance\Report\Filament\Resources\ReportingResource;

final class CreateReport extends CreateRecord
{
    protected static string $resource = ReportingResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');

        return app(CreateReportRecord::class)->handle((int) $tenant->getKey(), $data);
    }
}
