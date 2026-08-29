<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Filament\Resources\ReportingResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Report\Filament\Resources\ReportingResource;

final class ListReports extends ListRecords
{
    protected static string $resource = ReportingResource::class;
}
