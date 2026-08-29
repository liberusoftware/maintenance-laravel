<?php

use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Inventory\Actions\AdjustStock;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateStockItem;
use Liberu\Modules\Maintenance\Inventory\Actions\IssueStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReleaseReservedStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReserveStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReturnStock;
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

it('records every stock adjustment as an auditable movement', function () {
    $team = Team::factory()->create();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'filter-1', 'name' => 'Filter', 'quantity' => 3]);
    $adjusted = app(AdjustStock::class)->handle($team->id, $item, 2, 'receipt', 42, 'Received shipment');

    expect($adjusted->quantity)->toBe(5)
        ->and($adjusted->movements()->count())->toBe(1)
        ->and($adjusted->movements()->first()->quantity_before)->toBe(3)
        ->and($adjusted->movements()->first()->reason)->toBe('receipt');
});

it('reserves and releases only available stock', function () {
    $team = Team::factory()->create();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'filter-1', 'name' => 'Filter', 'quantity' => 5]);

    $item = app(ReserveStock::class)->handle($team->id, $item, 3);
    expect($item->reserved_quantity)->toBe(3)
        ->and($item->availableQuantity())->toBe(2);

    $item = app(ReleaseReservedStock::class)->handle($team->id, $item, 1);
    expect($item->reserved_quantity)->toBe(2)
        ->and($item->availableQuantity())->toBe(3);

    expect(fn () => app(ReserveStock::class)->handle($team->id, $item, 4))
        ->toThrow(ValidationException::class);
});

it('finds low and out of stock items using available quantities', function () {
    $team = Team::factory()->create();
    $create = app(CreateStockItem::class);
    $low = $create->handle($team->id, ['part_number' => 'low', 'name' => 'Low', 'quantity' => 3, 'reorder_level' => 3]);
    $out = $create->handle($team->id, ['part_number' => 'out', 'name' => 'Out', 'quantity' => 2, 'reorder_level' => 1]);
    app(ReserveStock::class)->handle($team->id, $out, 2);

    expect(StockItem::query()->where('team_id', $team->id)->lowStock()->pluck('id')->all())
        ->toContain($low->id, $out->id)
        ->and(StockItem::query()->where('team_id', $team->id)->outOfStock()->pluck('id')->all())
        ->toBe([$out->id]);
});

it('does not adjust stock below reserved quantities', function () {
    $team = Team::factory()->create();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'reserved', 'name' => 'Reserved filter', 'quantity' => 5]);
    app(ReserveStock::class)->handle($team->id, $item, 3);

    expect(fn () => app(AdjustStock::class)->handle($team->id, $item, -3))
        ->toThrow(ValidationException::class);
});

it('records explicit issues and returns through stock actions', function () {
    $team = Team::factory()->create();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'filter-1', 'name' => 'Filter', 'quantity' => 5]);

    $item = app(IssueStock::class)->handle($team->id, $item, 2, 42, 'Used on pump');
    expect($item->quantity)->toBe(3)->and($item->movements()->latest()->first()->reason)->toBe('issue');
    $item = app(ReturnStock::class)->handle($team->id, $item, 1, 42, 'Unused part');
    expect($item->quantity)->toBe(4)->and($item->movements()->where('reason', 'return')->exists())->toBeTrue();
});
