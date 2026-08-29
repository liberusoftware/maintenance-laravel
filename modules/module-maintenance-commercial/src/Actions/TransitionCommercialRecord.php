<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

final class TransitionCommercialRecord
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['proposed'],
        'proposed' => ['approved', 'rejected'],
        'approved' => ['fulfilled', 'cancelled'],
    ];

    public function handle(int $teamId, CommercialRecord $record, string $status): CommercialRecord
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        if (! in_array($status, self::TRANSITIONS[$record->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "A commercial record cannot transition from {$record->status} to {$status}."]);
        }

        $record->status = $status;
        $record->save();

        return $record->refresh();
    }
}
