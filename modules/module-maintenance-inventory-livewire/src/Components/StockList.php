<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Inventory\Actions\CreateStockItem;
use Liberu\Modules\Maintenance\Inventory\Models\StockItem;
use Livewire\Component;

class StockList extends Component
{
    public string $part_number = '';

    public string $name = '';

    public int $quantity = 0;

    public function save(CreateStockItem $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['part_number' => 'required|string|max:96', 'name' => 'required|string|max:255', 'quantity' => 'required|integer|min:0']);
        $create->handle((int) $id, ['part_number' => $this->part_number, 'name' => $this->name, 'quantity' => $this->quantity]);
        $this->reset(['part_number', 'name', 'quantity']);
        $this->dispatch('maintenance-stock-created');
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $items = $id === null ? collect() : StockItem::where('team_id', $id)->orderBy('name')->get();

        return view('module-maintenance-inventory-livewire::livewire.stock-list', compact('items'));
    }
}
