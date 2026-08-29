<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource;

final class ListOrganizations extends ListRecords
{
    protected static string $resource = OrganizationResource::class;
}
