<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;

class ApproveTimeEntry
{
    public function handle(int $teamId, TimeEntry $entry, int $approverId): TimeEntry
    {
        if ((int) $entry->team_id !== $teamId) {
            abort(404);
        }if ($entry->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending entries can be approved.']);
        }if ((int) $entry->user_id === $approverId) {
            throw ValidationException::withMessages(['approver' => 'The engineer cannot approve their own entry.']);
        }$entry->status = 'approved';
        $entry->save();

        return $entry->refresh();
    }
}
