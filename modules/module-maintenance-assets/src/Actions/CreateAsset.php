<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;
use Liberu\Modules\Maintenance\Assets\Models\AssetCategory;

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
        if (isset($attributes['parent_id']) && ! Asset::query()->where('team_id', $teamId)->whereKey($attributes['parent_id'])->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'The parent asset is invalid.']);
        }
        if (isset($attributes['category_id']) && ! AssetCategory::query()->where('team_id', $teamId)->whereKey($attributes['category_id'])->exists()) {
            throw ValidationException::withMessages(['category_id' => 'The asset category is invalid.']);
        }

        return DB::transaction(fn () => Asset::query()->create(array_merge($attributes, ['team_id' => $teamId, 'name' => $name, 'code' => $code, 'status' => $attributes['status'] ?? 'active', 'condition' => $attributes['condition'] ?? 'unknown'])));
    }
}
