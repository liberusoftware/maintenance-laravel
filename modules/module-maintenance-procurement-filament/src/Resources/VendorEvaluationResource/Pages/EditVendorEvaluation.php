<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorEvaluationResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Modules\Maintenance\Procurement\Actions\UpdateVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Filament\Resources\VendorEvaluationResource;

final class EditVendorEvaluation extends EditRecord
{
    protected static string $resource = VendorEvaluationResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam;
        abort_if($tenant === null, 403);

        return app(UpdateVendorPerformanceEvaluation::class)->handle((int) $tenant->getKey(), $record, $data);
    }
}
