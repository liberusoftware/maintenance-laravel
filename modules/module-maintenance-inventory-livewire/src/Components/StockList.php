<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Inventory\Actions\AdjustStock;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateStockItem;
use Liberu\Modules\Maintenance\Inventory\Actions\DeleteStockItem;
use Liberu\Modules\Maintenance\Inventory\Actions\IssueStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReleaseReservedStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReserveStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReturnStock;
use Liberu\Modules\Maintenance\Inventory\Actions\UpdateStockItem;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;
use Livewire\Component;

class StockList extends Component
{
    public string $part_number = '';

    public string $name = '';

    public string $description = '';

    public string $category = '';

    public string $location = '';

    public string $supplier_name = '';

    public int $reorder_level = 0;

    public int $reorder_quantity = 0;

    public string $unit_cost = '0';

    public int $quantity = 0;

    public int $reservationQuantity = 1;

    public int $movementQuantity = 1;

    public ?int $editingItemId = null;

    public function save(CreateStockItem $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['part_number' => 'required|string|max:96', 'name' => 'required|string|max:255', 'quantity' => 'required|integer|min:0', 'reorder_level' => 'required|integer|min:0', 'reorder_quantity' => 'required|integer|min:0', 'unit_cost' => 'required|numeric|min:0']);
        $create->handle((int) $id, ['part_number' => $this->part_number, 'name' => $this->name, 'description' => $this->description, 'category' => $this->category, 'location' => $this->location, 'supplier_name' => $this->supplier_name, 'quantity' => $this->quantity, 'reorder_level' => $this->reorder_level, 'reorder_quantity' => $this->reorder_quantity, 'unit_cost' => $this->unit_cost]);
        $this->reset(['part_number', 'name', 'description', 'category', 'location', 'supplier_name', 'quantity', 'reorder_level', 'reorder_quantity', 'unit_cost']);
        $this->dispatch('maintenance-stock-created');
    }

    public function edit(int $itemId): void
    {
        $item = $this->itemForCurrentTeam($itemId);
        $this->editingItemId = $item->getKey();
        $this->part_number = $item->part_number;
        $this->name = $item->name;
        $this->quantity = (int) $item->quantity;
        $this->description = (string) ($item->description ?? '');
        $this->category = (string) ($item->category ?? '');
        $this->location = (string) ($item->location ?? '');
        $this->supplier_name = (string) ($item->supplier_name ?? '');
        $this->reorder_level = (int) $item->reorder_level;
        $this->reorder_quantity = (int) $item->reorder_quantity;
        $this->unit_cost = (string) ($item->unit_cost ?? '0');
    }

    public function update(UpdateStockItem $update, AdjustStock $adjust): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingItemId === null, 403);
        $this->validate(['part_number' => 'required|string|max:96', 'name' => 'required|string|max:255', 'quantity' => 'required|integer|min:0', 'reorder_level' => 'required|integer|min:0', 'reorder_quantity' => 'required|integer|min:0', 'unit_cost' => 'required|numeric|min:0']);
        $item = $this->itemForCurrentTeam($this->editingItemId);
        $update->handle((int) $teamId, $item, ['part_number' => $this->part_number, 'name' => $this->name, 'description' => $this->description, 'category' => $this->category, 'location' => $this->location, 'supplier_name' => $this->supplier_name, 'reorder_level' => $this->reorder_level, 'reorder_quantity' => $this->reorder_quantity, 'unit_cost' => $this->unit_cost]);
        if ((int) $this->quantity !== (int) $item->quantity) {
            $adjust->handle((int) $teamId, $item, (int) $this->quantity - (int) $item->quantity);
        }
        $this->cancelEdit();
    }

    public function delete(int $itemId, DeleteStockItem $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->itemForCurrentTeam($itemId));
    }

    public function reserve(int $itemId, ReserveStock $reserve): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['reservationQuantity' => 'required|integer|min:1']);
        $reserve->handle((int) $teamId, $this->itemForCurrentTeam($itemId), $this->reservationQuantity);
    }

    public function release(int $itemId, ReleaseReservedStock $release): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['reservationQuantity' => 'required|integer|min:1']);
        $release->handle((int) $teamId, $this->itemForCurrentTeam($itemId), $this->reservationQuantity);
    }

    public function issue(int $itemId, IssueStock $issue): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['movementQuantity' => 'required|integer|min:1']);
        $issue->handle((int) $teamId, $this->itemForCurrentTeam($itemId), $this->movementQuantity, auth()->id());
        $this->reset('movementQuantity');
    }

    public function return(int $itemId, ReturnStock $returnStock): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['movementQuantity' => 'required|integer|min:1']);
        $returnStock->handle((int) $teamId, $this->itemForCurrentTeam($itemId), $this->movementQuantity, auth()->id());
        $this->reset('movementQuantity');
    }

    public function cancelEdit(): void
    {
        $this->reset(['part_number', 'name', 'description', 'category', 'location', 'supplier_name', 'quantity', 'reorder_level', 'reorder_quantity', 'unit_cost', 'editingItemId']);
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $items = $id === null ? collect() : StockItem::where('team_id', $id)->orderBy('name')->get();

        return view('module-maintenance-inventory-livewire::livewire.stock-list', compact('items'));
    }

    private function itemForCurrentTeam(int $itemId): StockItem
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return StockItem::query()->where('team_id', $teamId)->findOrFail($itemId);
    }
}
