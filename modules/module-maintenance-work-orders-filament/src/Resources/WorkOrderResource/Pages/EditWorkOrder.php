<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Modules\Maintenance\WorkOrders\Filament\Resources\WorkOrderResource;

class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;
}
