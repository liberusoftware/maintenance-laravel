<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorEvaluationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorEvaluationResource;

final class ListVendorEvaluations extends ListRecords
{
    protected static string $resource = VendorEvaluationResource::class;
}
