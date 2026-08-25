<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = auth()->user()?->currentTeam?->getKey();
        abort_if($data['team_id'] === null, 403);

        return $data;
    }
}
