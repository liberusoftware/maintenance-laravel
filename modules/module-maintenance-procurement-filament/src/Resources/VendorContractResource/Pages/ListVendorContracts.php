<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource;

final class ListVendorContracts extends ListRecords
{
    protected static string $resource = VendorContractResource::class;
}
