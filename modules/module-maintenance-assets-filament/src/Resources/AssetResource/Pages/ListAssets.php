<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;
}
