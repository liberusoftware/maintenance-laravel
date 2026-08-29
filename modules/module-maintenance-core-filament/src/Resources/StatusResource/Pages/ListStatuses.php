<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\StatusResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Core\Filament\Resources\StatusResource;

final class ListStatuses extends ListRecords
{
    protected static string $resource = StatusResource::class;
}
