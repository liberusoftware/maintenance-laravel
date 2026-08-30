<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionFollowUp;

final class CreateInspectionFollowUp
{
    public function handle(int $teamId, Inspection $inspection, array $attributes): InspectionFollowUp
    {
        abort_unless((int) $inspection->team_id === $teamId, 404);
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'A follow-up title is required.']);
        }

        return DB::transaction(fn (): InspectionFollowUp => InspectionFollowUp::create(array_merge($attributes, ['team_id' => $teamId, 'inspection_id' => $inspection->getKey(), 'title' => $title, 'status' => 'open'])));
    }
}
