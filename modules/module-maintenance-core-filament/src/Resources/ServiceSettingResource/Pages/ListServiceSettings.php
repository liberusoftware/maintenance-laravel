<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\ServiceSettingResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Modules\Maintenance\Core\Filament\Resources\ServiceSettingResource;

final class ListServiceSettings extends ListRecords
{
    protected static string $resource = ServiceSettingResource::class;
}
