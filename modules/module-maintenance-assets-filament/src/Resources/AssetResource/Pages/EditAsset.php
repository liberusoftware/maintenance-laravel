<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Assets\Actions\UpdateAsset as UpdateAssetAction;
use Liberu\Modules\Maintenance\Assets\Filament\Resources\AssetResource;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(UpdateAssetAction::class)->handle((int) $teamId, $record, $data);
    }
}
