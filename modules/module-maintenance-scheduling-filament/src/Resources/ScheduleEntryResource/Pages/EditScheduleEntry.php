<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource;

class EditScheduleEntry extends EditRecord
{
    protected static string $resource = ScheduleEntryResource::class;
}
