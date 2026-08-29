<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource;

final class ListInspections extends ListRecords
{
    protected static string $resource = InspectionResource::class;
}
