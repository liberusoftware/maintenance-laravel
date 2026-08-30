<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\AssetCategory;

final class CreateAssetCategory
{
    public function handle(int $teamId, array $attributes): AssetCategory
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $code = strtoupper(trim((string) ($attributes['code'] ?? '')));
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'Name and code are required.']);
        }
        if (AssetCategory::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'The category code is already in use.']);
        }
        if (isset($attributes['parent_id']) && ! AssetCategory::query()->where('team_id', $teamId)->whereKey($attributes['parent_id'])->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'The parent category is invalid.']);
        }

        return DB::transaction(fn (): AssetCategory => AssetCategory::create(array_merge($attributes, ['team_id' => $teamId, 'name' => $name, 'code' => $code])));
    }
}
