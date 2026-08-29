<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\PriorityResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Core\Filament\Resources\PriorityResource;

final class ListPriorities extends ListRecords
{
    protected static string $resource = PriorityResource::class;
}
