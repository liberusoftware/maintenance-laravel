<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inventory\Models\InventoryLocation;

final class CreateInventoryLocation
{
    public function handle(int $teamId, array $attributes): InventoryLocation
    {
        $code = strtoupper(trim((string) ($attributes['code'] ?? '')));
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($code === '' || $name === '') {
            throw ValidationException::withMessages(['code' => 'A location code and name are required.']);
        }
        if (InventoryLocation::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'The location code is already in use.']);
        }

        return DB::transaction(fn (): InventoryLocation => InventoryLocation::create(array_merge($attributes, ['team_id' => $teamId, 'code' => $code, 'name' => $name, 'type' => $attributes['type'] ?? 'warehouse'])));
    }
}
