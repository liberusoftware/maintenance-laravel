<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Scheduling\Models\Territory;

final class UpdateTerritory
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, Territory $territory, array $attributes): Territory
    {
        abort_unless((int) $territory->team_id === $teamId, 404);
        $code = array_key_exists('code', $attributes) ? trim((string) $attributes['code']) : $territory->code;
        $name = array_key_exists('name', $attributes) ? trim((string) $attributes['name']) : $territory->name;
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['territory' => 'A territory name and code are required.']);
        }
        if (Territory::query()->where('team_id', $teamId)->where('code', $code)->whereKeyNot($territory->getKey())->exists()) {
            throw ValidationException::withMessages(['code' => 'That territory code already exists.']);
        }

        return DB::transaction(function () use ($territory, $attributes, $name, $code): Territory {
            $territory->fill(array_merge($attributes, ['name' => $name, 'code' => $code]));
            $territory->save();

            return $territory->refresh();
        });
    }
}
