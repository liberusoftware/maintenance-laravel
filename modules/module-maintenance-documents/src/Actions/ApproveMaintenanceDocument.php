<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Documents\Models\MaintenanceDocument;

final class ApproveMaintenanceDocument
{
    public function handle(int $teamId, MaintenanceDocument $document, int $userId): MaintenanceDocument
    {
        abort_unless((int) $document->team_id === $teamId, 404);

        return DB::transaction(function () use ($document, $userId): MaintenanceDocument {
            $document->forceFill(['approval_status' => 'approved', 'approved_by' => $userId, 'approved_at' => now(), 'status' => 'active'])->save();

            return $document->refresh();
        });
    }
}
