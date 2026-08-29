<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;
}
