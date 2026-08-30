<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Filament\Resources\ComplianceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Compliance\Filament\Resources\ComplianceResource;

final class ListCompliance extends ListRecords
{
    protected static string $resource = ComplianceResource::class;
}
