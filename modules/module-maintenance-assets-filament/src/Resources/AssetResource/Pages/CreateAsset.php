<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Assets\Actions\CreateAsset as CreateAssetAction;
use Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(CreateAssetAction::class)->handle((int) $teamId, $data);
    }
}
