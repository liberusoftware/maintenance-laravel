<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Modules\Maintenance\Inspections\Filament\Resources\InspectionResource;

class EditInspection extends EditRecord
{
    protected static string $resource = InspectionResource::class;
}
