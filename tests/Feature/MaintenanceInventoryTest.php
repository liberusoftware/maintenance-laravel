<?php

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Modules\Maintenance\Inventory\Actions\AdjustStock;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateStockItem;
use Liberu\Modules\Maintenance\Inventory\Actions\CountStock;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateInventoryLocation;
use Liberu\Modules\Maintenance\Inventory\Actions\SetStockLevel;
use Liberu\Modules\Maintenance\Inventory\Actions\TransferStock;
use Liberu\Modules\Maintenance\Inventory\Actions\IssueStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReleaseReservedStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReserveStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReturnStock;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

it('supports warehouse and van stock levels with tenant-scoped transfers', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'FILTER-1', 'name' => 'Air filter']);
    $warehouse = app(CreateInventoryLocation::class)->handle($team->id, ['code' => 'MAIN', 'name' => 'Main warehouse']);
    $van = app(CreateInventoryLocation::class)->handle($team->id, ['code' => 'VAN-1', 'name' => 'Van 1', 'type' => 'van']);
    app(SetStockLevel::class)->handle($team->id, $item, $warehouse, 10, $user->id);

    app(TransferStock::class)->handle($team->id, $item, $warehouse, $van, 4, $user->id, 'Dispatch stock');

    expect($warehouse->levels()->first()->quantity)->toBe(6)
        ->and($van->levels()->first()->quantity)->toBe(4)
        ->and($item->fresh()->quantity)->toBe(10)
        ->and($item->fresh()->movements()->where('reason', 'transfer_out')->exists())->toBeTrue();
});

it('exposes inventory locations and transfers through the API', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['current_team_id' => $team->id])->save();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'PUMP-1', 'name' => 'Pump']);
    $token = $user->createToken('inventory-location-api-test')->plainTextToken;

    $location = $this->withToken($token)->postJson('/api/v1/maintenance/inventory/locations', ['code' => 'MAIN', 'name' => 'Main warehouse'])->assertCreated()->json('data.id');
    $van = $this->withToken($token)->postJson('/api/v1/maintenance/inventory/locations', ['code' => 'VAN-1', 'name' => 'Van 1', 'type' => 'van'])->assertCreated()->json('data.id');
    $this->withToken($token)->postJson("/api/v1/maintenance/inventory/locations/{$location}/levels", ['stock_item_id' => $item->id, 'quantity' => 8])->assertCreated();
    $this->withToken($token)->postJson('/api/v1/maintenance/inventory/transfers', ['stock_item_id' => $item->id, 'from_location_id' => $location, 'to_location_id' => $van, 'quantity' => 3])->assertOk();
    $this->withToken($token)->getJson('/api/v1/maintenance/inventory/locations')->assertOk()->assertJsonCount(2, 'data');
});
use Liberu\Modules\Maintenance\Inventory\Queries\ReorderRecommendations;

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

it('exposes low and out of stock status helpers using available quantities', function () {
    $team = Team::factory()->create();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'status', 'name' => 'Status filter', 'quantity' => 4, 'reorder_level' => 2]);

    expect($item->isLowStock())->toBeFalse()
        ->and($item->isOutOfStock())->toBeFalse();

    app(ReserveStock::class)->handle($team->id, $item, 2);
    $item->refresh();

    expect($item->isLowStock())->toBeTrue()
        ->and($item->isOutOfStock())->toBeFalse();

    app(ReserveStock::class)->handle($team->id, $item, 2);
    $item->refresh();

    expect($item->isLowStock())->toBeTrue()
        ->and($item->isOutOfStock())->toBeTrue();
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

it('records physical counts and produces reorder recommendations', function () {
    $team = Team::factory()->create();
    $item = app(CreateStockItem::class)->handle($team->id, ['part_number' => 'filter-count', 'name' => 'Counted filter', 'quantity' => 8, 'reorder_level' => 10, 'reorder_quantity' => 20]);

    $counted = app(CountStock::class)->handle($team->id, $item, 4, 42, 'Cycle count');
    $recommendations = app(ReorderRecommendations::class)->handle($team->id);

    expect($counted->quantity)->toBe(4)
        ->and($counted->movements()->latest()->first()->reason)->toBe('count')
        ->and($recommendations->first())->toMatchArray(['stock_item_id' => $item->id, 'recommended_quantity' => 16]);
});

it('retains legacy inventory part details in the modular stock item', function () {
    $team = Team::factory()->create();
    $item = app(CreateStockItem::class)->handle($team->id, [
        'part_number' => 'filter-legacy', 'name' => 'Air filter', 'description' => 'MERV 13 filter',
        'category' => 'HVAC', 'supplier_name' => 'Acme Supply', 'lead_time_days' => 5,
        'reorder_level' => 2, 'reorder_quantity' => 10, 'unit_cost' => 12.50, 'notes' => 'Store dry',
    ]);

    expect($item->description)->toBe('MERV 13 filter')
        ->and($item->category)->toBe('HVAC')
        ->and($item->supplier_name)->toBe('Acme Supply')
        ->and($item->lead_time_days)->toBe(5)
        ->and($item->reorder_quantity)->toBe(10)
        ->and($item->unit_cost)->toBe('12.50');
});
