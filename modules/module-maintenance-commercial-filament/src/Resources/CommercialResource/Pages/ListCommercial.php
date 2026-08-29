<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Filament\Resources\CommercialResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Commercial\Filament\Resources\CommercialResource;

final class ListCommercial extends ListRecords
{
    protected static string $resource = CommercialResource::class;
}
