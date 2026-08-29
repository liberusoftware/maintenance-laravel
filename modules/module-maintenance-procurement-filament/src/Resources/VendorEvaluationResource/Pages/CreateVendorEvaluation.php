<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorEvaluationResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Procurement\Actions\CreateVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorEvaluationResource;

final class CreateVendorEvaluation extends CreateRecord
{
    protected static string $resource = VendorEvaluationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403);
        $data['evaluated_by'] = auth()->id();

        return app(CreateVendorPerformanceEvaluation::class)->handle((int) $tenant->getKey(), $data);
    }
}
