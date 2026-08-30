<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Documents\Models\MaintenanceDocument;

final class CreateMaintenanceDocument
{
    public function handle(int $teamId, array $attributes): MaintenanceDocument
    {
        return DB::transaction(fn (): MaintenanceDocument => MaintenanceDocument::query()->create(array_merge(['team_id' => $teamId, 'status' => 'draft', 'approval_status' => 'pending'], $attributes))->refresh());
    }
}
