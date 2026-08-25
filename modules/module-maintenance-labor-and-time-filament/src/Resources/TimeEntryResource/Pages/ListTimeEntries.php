<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;
}
