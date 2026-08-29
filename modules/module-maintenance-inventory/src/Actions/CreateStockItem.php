<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

class CreateStockItem
{
    public function handle(int $teamId, array $attributes): StockItem
    {
        $part = strtoupper(trim((string) ($attributes['part_number'] ?? '')));
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($part === '' || $name === '') {
            throw ValidationException::withMessages(['part_number' => 'Part number and name are required.']);
        }if (StockItem::where('team_id', $teamId)->where('part_number', $part)->exists()) {
            throw ValidationException::withMessages(['part_number' => 'The part number is already in use.']);
        }

        return DB::transaction(fn () => StockItem::create(array_merge($attributes, ['team_id' => $teamId, 'part_number' => $part, 'name' => $name, 'quantity' => (int) ($attributes['quantity'] ?? 0), 'reorder_level' => (int) ($attributes['reorder_level'] ?? 0)])));
    }
}
