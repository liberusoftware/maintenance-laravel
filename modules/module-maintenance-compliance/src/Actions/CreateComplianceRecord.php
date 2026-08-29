<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;

class CreateComplianceRecord
{
    public function handle(int $teamId, array $attributes): ComplianceRecord
    {
        $kind = trim((string) ($attributes['kind'] ?? ''));
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($kind === '' || $title === '') {
            throw ValidationException::withMessages(['title' => 'A kind and title are required.']);
        }

        return DB::transaction(fn () => ComplianceRecord::create(array_merge($attributes, ['team_id' => $teamId, 'kind' => $kind, 'title' => $title, 'status' => $attributes['status'] ?? 'draft'])));
    }
}
