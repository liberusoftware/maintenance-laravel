<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portals\Filament\Resources\PortalsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Portals\Filament\Resources\PortalsResource;

final class ListPortals extends ListRecords
{
    protected static string $resource = PortalsResource::class;
}
