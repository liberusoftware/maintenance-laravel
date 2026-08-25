<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portal\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Portal\Models\PortalRecord;

class CreatePortalRecord
{
    public function handle(int $teamId, array $attributes): PortalRecord
    {
        $kind = trim((string) ($attributes['kind'] ?? ''));
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($kind === '' || $title === '') {
            throw ValidationException::withMessages(['title' => 'A kind and title are required.']);
        }

        return DB::transaction(fn () => PortalRecord::create(array_merge($attributes, ['team_id' => $teamId, 'kind' => $kind, 'title' => $title, 'status' => $attributes['status'] ?? 'draft'])));
    }
}
