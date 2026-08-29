<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

final class RejectTimeEntry
{
    public function handle(int $teamId, TimeEntry $entry, int $approverId, ?string $reason = null): TimeEntry
    {
        abort_unless((int) $entry->team_id === $teamId, 404);
        if ($entry->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending entries can be rejected.']);
        }
        if ((int) $entry->user_id === $approverId) {
            throw ValidationException::withMessages(['rejected_by' => 'The engineer cannot reject their own entry.']);
        }

        $entry->status = 'rejected';
        if ($reason !== null && trim($reason) !== '') {
            $entry->description = trim((string) $entry->description).' [Rejection: '.trim($reason).']';
        }
        $entry->save();

        return $entry->refresh();
    }
}
