<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

class CompleteInspection
{
    public function handle(int $teamId, Inspection $inspection, string $outcome): Inspection
    {
        if ((int) $inspection->team_id !== $teamId) {
            abort(404);
        }
        if ($inspection->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft inspections can be completed.']);
        }
        if (! in_array($outcome, ['pass', 'fail', 'conditional'], true)) {
            throw ValidationException::withMessages(['outcome' => 'The inspection outcome is invalid.']);
        }
        $inspection->status = 'completed';
        $inspection->outcome = $outcome;
        $inspection->inspected_at = $inspection->inspected_at ?? now();
        $inspection->save();

        return $inspection->refresh();
    }
}
