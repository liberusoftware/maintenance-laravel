<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Filament\Resources\ServiceSettingResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Core\Actions\SetServiceSetting;
use Liberu\Modules\Maintenance\Core\Filament\Resources\ServiceSettingResource;

final class EditServiceSetting extends EditRecord
{
    protected static string $resource = ServiceSettingResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403, 'A current team context is required.');

        return app(SetServiceSetting::class)->execute((int) $tenant->getKey(), (string) $record->key, $data['value'] ?? null, (bool) ($data['is_encrypted'] ?? false));
    }
}
