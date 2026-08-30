<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionFollowUp;

final class CompleteInspectionFollowUp
{
    public function handle(int $teamId, InspectionFollowUp $followUp, int $actorId): InspectionFollowUp
    {
        abort_unless((int) $followUp->team_id === $teamId, 404);
        if ($followUp->status !== 'open') {
            throw ValidationException::withMessages(['status' => 'Only open inspection follow-ups can be completed.']);
        }

        return DB::transaction(function () use ($followUp, $actorId): InspectionFollowUp {
            $followUp->forceFill(['status' => 'completed', 'completed_at' => now(), 'completed_by' => $actorId])->save();

            return $followUp->refresh();
        });
    }
}
