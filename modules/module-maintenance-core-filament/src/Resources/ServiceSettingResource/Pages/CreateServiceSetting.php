<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\ServiceSettingResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Core\Actions\SetServiceSetting;
use Liberu\Modules\Maintenance\Core\Filament\Resources\ServiceSettingResource;

final class CreateServiceSetting extends CreateRecord
{
    protected static string $resource = ServiceSettingResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');

        return app(SetServiceSetting::class)->execute((int) $tenant->getKey(), (string) $data['key'], $data['value'] ?? null, (bool) ($data['is_encrypted'] ?? false));
    }
}
