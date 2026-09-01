<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceIncident;

final class CreateComplianceIncident
{
    public function handle(int $teamId, array $attributes): ComplianceIncident
    {
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'An incident title is required.']);
        }

        return DB::transaction(fn (): ComplianceIncident => ComplianceIncident::create(array_merge($attributes, ['team_id' => $teamId, 'title' => $title]))->refresh());
    }
}
