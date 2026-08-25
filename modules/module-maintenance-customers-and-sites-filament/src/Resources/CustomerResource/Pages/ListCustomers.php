<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\CustomerResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
}
