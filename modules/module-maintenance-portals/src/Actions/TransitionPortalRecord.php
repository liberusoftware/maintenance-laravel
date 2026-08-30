<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portal\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Portal\Models\PortalRecord;

final class TransitionPortalRecord
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['in_progress', 'rejected'],
        'in_progress' => ['resolved', 'rejected'],
    ];

    public function handle(int $teamId, PortalRecord $record, string $status): PortalRecord
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        if (! in_array($status, self::TRANSITIONS[$record->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "A portal record cannot transition from {$record->status} to {$status}."]);
        }

        $record->status = $status;
        $record->save();

        return $record->refresh();
    }
}
