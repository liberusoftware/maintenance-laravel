<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Scheduling\Filament\Resources\ScheduleEntryResource;

class ListScheduleEntries extends ListRecords
{
    protected static string $resource = ScheduleEntryResource::class;
}
