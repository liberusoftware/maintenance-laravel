<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

class CreateCommercialRecord
{
    public function handle(int $teamId, array $attributes): CommercialRecord
    {
        $kind = trim((string) ($attributes['kind'] ?? ''));
        $title = trim((string) ($attributes['title'] ?? ''));
        if ($kind === '' || $title === '') {
            throw ValidationException::withMessages(['title' => 'A kind and title are required.']);
        }

        return DB::transaction(fn () => CommercialRecord::create(array_merge($attributes, ['team_id' => $teamId, 'kind' => $kind, 'title' => $title, 'status' => $attributes['status'] ?? 'draft'])));
    }
}
