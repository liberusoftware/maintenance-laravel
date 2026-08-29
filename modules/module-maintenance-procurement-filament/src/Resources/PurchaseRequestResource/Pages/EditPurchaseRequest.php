<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\PurchaseRequestResource;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;
}
