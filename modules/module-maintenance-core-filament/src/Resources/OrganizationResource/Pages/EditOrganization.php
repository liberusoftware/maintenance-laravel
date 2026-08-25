<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Modules\Maintenance\Core\Filament\Resources\OrganizationResource;

final class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;
}
