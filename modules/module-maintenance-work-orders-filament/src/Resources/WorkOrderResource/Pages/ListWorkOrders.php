<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource;

class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;
}
