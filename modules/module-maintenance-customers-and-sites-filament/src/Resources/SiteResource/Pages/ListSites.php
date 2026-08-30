<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\SiteResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\SiteResource;

class ListSites extends ListRecords
{
    protected static string $resource = SiteResource::class;
}
