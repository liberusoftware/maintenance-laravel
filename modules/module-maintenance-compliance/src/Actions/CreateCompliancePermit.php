<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Compliance\Models\CompliancePermit;

final class CreateCompliancePermit
{
    public function handle(int $teamId, array $attributes): CompliancePermit
    {
        $number = trim((string) ($attributes['number'] ?? ''));
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($number === '' || $title === '') {
            throw ValidationException::withMessages(['permit' => 'A permit number and title are required.']);
        }
        if (CompliancePermit::query()->where('team_id', $teamId)->where('number', $number)->exists()) {
            throw ValidationException::withMessages(['number' => 'That permit number already exists.']);
        }

        return DB::transaction(fn (): CompliancePermit => CompliancePermit::create(array_merge($attributes, ['team_id' => $teamId, 'number' => $number, 'title' => $title]))->refresh());
    }
}
