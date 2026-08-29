<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

final class UpdateStockItem
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, StockItem $item, array $attributes): StockItem
    {
        abort_unless((int) $item->team_id === $teamId, 404);
        $partNumber = array_key_exists('part_number', $attributes) ? strtoupper(trim((string) $attributes['part_number'])) : $item->part_number;
        $name = array_key_exists('name', $attributes) ? trim((string) $attributes['name']) : $item->name;
        if ($partNumber === '' || $name === '') {
            throw ValidationException::withMessages(['part_number' => 'Part number and name are required.']);
        }
        if (StockItem::query()->where('team_id', $teamId)->where('part_number', $partNumber)->whereKeyNot($item->getKey())->exists()) {
            throw ValidationException::withMessages(['part_number' => 'The part number is already in use.']);
        }

        return DB::transaction(function () use ($item, $attributes, $partNumber, $name): StockItem {
            $item->fill(array_merge($attributes, ['part_number' => $partNumber, 'name' => $name]));
            $item->save();

            return $item->refresh();
        });
    }
}
