<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Procurement\Actions\UpdateVendorContract;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource;

final class EditVendorContract extends EditRecord
{
    protected static string $resource = VendorContractResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403);

        return app(UpdateVendorContract::class)->handle((int) $tenant->getKey(), $record, $data);
    }
}
