<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;

class CreateAsset
{
    public function handle(int $teamId, array $attributes): Asset
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $code = strtoupper(trim((string) ($attributes['code'] ?? '')));
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'Name and code are required.']);
        }
        if (Asset::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'The asset code is already in use.']);
        }

        return DB::transaction(fn () => Asset::query()->create(array_merge($attributes, ['team_id' => $teamId, 'name' => $name, 'code' => $code, 'status' => $attributes['status'] ?? 'active', 'condition' => $attributes['condition'] ?? 'unknown'])));
    }
}
