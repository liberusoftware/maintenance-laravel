<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\SiteResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateSite as UpdateSiteAction;
use Liberu\Modules\Maintenance\CustomersAndSites\Filament\Resources\SiteResource;

class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return app(UpdateSiteAction::class)->handle((int) $teamId, $record, $data);
    }
}
