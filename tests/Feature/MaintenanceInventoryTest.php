<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Inventory\Actions\AdjustStock;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateStockItem;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

it('creates and adjusts tenant-scoped stock', function () {
    $team = Team::factory()->create();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'filter-1', 'name' => 'Filter', 'quantity' => 3]);
    $item = app(AdjustStock::class)->handle($team->id, $item, 2);

    expect($item)->toBeInstanceOf(StockItem::class)
        ->and($item->team_id)->toBe($team->id)
        ->and($item->part_number)->toBe('FILTER-1')
        ->and($item->quantity)->toBe(5);
});

it('prevents inventory from becoming negative', function () {
    $team = Team::factory()->create();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'filter-1', 'name' => 'Filter', 'quantity' => 1]);

    expect(fn () => app(AdjustStock::class)->handle($team->id, $item, -2))
        ->toThrow(ValidationException::class);
});
