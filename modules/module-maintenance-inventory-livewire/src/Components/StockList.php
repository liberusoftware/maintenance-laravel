<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Inventory\Actions\AdjustStock;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateStockItem;
use Liberu\Modules\Maintenance\Inventory\Actions\DeleteStockItem;
use Liberu\Modules\Maintenance\Inventory\Actions\ReleaseReservedStock;
use Liberu\Modules\Maintenance\Inventory\Actions\ReserveStock;
use Liberu\Modules\Maintenance\Inventory\Actions\UpdateStockItem;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;
use Livewire\Component;

class StockList extends Component
{
    public string $part_number = '';

    public string $name = '';

    public int $quantity = 0;

    public int $reservationQuantity = 1;

    public ?int $editingItemId = null;

    public function save(CreateStockItem $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['part_number' => 'required|string|max:96', 'name' => 'required|string|max:255', 'quantity' => 'required|integer|min:0']);
        $create->handle((int) $id, ['part_number' => $this->part_number, 'name' => $this->name, 'quantity' => $this->quantity]);
        $this->reset(['part_number', 'name', 'quantity']);
        $this->dispatch('maintenance-stock-created');
    }

    public function edit(int $itemId): void
    {
        $item = $this->itemForCurrentTeam($itemId);
        $this->editingItemId = $item->getKey();
        $this->part_number = $item->part_number;
        $this->name = $item->name;
        $this->quantity = (int) $item->quantity;
    }

    public function update(UpdateStockItem $update, AdjustStock $adjust): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingItemId === null, 403);
        $this->validate(['part_number' => 'required|string|max:96', 'name' => 'required|string|max:255', 'quantity' => 'required|integer|min:0']);
        $item = $this->itemForCurrentTeam($this->editingItemId);
        $update->handle((int) $teamId, $item, ['part_number' => $this->part_number, 'name' => $this->name]);
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

    public function cancelEdit(): void
    {
        $this->reset(['part_number', 'name', 'quantity', 'editingItemId']);
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
