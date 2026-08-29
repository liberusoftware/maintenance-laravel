<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Procurement\Actions\CreateVendorContract as CreateVendorContractAction;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorContractResource;

final class CreateVendorContract extends CreateRecord
{
    protected static string $resource = VendorContractResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403);

        return app(CreateVendorContractAction::class)->handle((int) $tenant->getKey(), $data);
    }
}
