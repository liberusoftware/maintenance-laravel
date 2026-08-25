<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\CustomerResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\CustomerResource;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;
}
