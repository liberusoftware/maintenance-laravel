<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Modules\Maintenance\LaborAndTime\Filament\Resources\TimeEntryResource;

class EditTimeEntry extends EditRecord
{
    protected static string $resource = TimeEntryResource::class;
}
