<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Compliance\Models\CorrectiveAction;

final class CompleteCorrectiveAction
{
    public function handle(int $teamId, CorrectiveAction $action, int $actorId): CorrectiveAction
    {
        abort_unless((int) $action->team_id === $teamId, 404);
        if ($action->status === 'completed') {
            throw ValidationException::withMessages(['status' => 'The corrective action is already completed.']);
        }

        return DB::transaction(function () use ($action, $actorId): CorrectiveAction {
            $action->forceFill(['status' => 'completed', 'completed_at' => now(), 'completed_by' => $actorId])->save();

            return $action->refresh();
        });
    }
}
