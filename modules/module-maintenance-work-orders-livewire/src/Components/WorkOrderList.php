<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;
use Livewire\Component;

class WorkOrderList extends Component
{
    public string $title = '';

    public string $description = '';

    public function save(CreateWorkOrder $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000']);
        $create->handle((int) $id, ['title' => $this->title, 'description' => $this->description]);
        $this->reset(['title', 'description']);
        $this->dispatch('maintenance-work-order-created');
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $orders = $id === null ? collect() : WorkOrder::where('team_id', $id)->latest()->get();

        return view('module-maintenance-work-orders-livewire::livewire.work-order-list', compact('orders'));
    }
}
