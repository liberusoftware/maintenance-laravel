<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderComment;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\DeleteWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\UpdateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;
use Livewire\Component;

class WorkOrderList extends Component
{
    public string $title = '';

    public string $description = '';

    public ?int $editingOrderId = null;

    public string $comment = '';

    public bool $commentIsInternal = false;

    public function save(CreateWorkOrder $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000']);
        $create->handle((int) $id, ['title' => $this->title, 'description' => $this->description]);
        $this->reset(['title', 'description']);
        $this->dispatch('maintenance-work-order-created');
    }

    public function edit(int $orderId): void
    {
        $order = $this->orderForCurrentTeam($orderId);
        $this->editingOrderId = $order->getKey();
        $this->title = $order->title;
        $this->description = (string) ($order->description ?? '');
    }

    public function update(UpdateWorkOrder $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingOrderId === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000']);
        $update->handle((int) $teamId, $this->orderForCurrentTeam($this->editingOrderId), ['title' => $this->title, 'description' => $this->description]);
        $this->cancelEdit();
    }

    public function delete(int $orderId, DeleteWorkOrder $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->orderForCurrentTeam($orderId));
    }

    public function cancelEdit(): void
    {
        $this->reset(['title', 'description', 'editingOrderId']);
    }

    public function addComment(int $orderId, AddWorkOrderComment $add): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || auth()->id() === null, 403);
        $this->validate(['comment' => ['required', 'string', 'max:10000'], 'commentIsInternal' => ['boolean']]);
        $add->handle((int) $teamId, $this->orderForCurrentTeam($orderId), (int) auth()->id(), $this->comment, $this->commentIsInternal);
        $this->reset(['comment', 'commentIsInternal']);
        $this->dispatch('maintenance-work-order-comment-added');
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $orders = $id === null ? collect() : WorkOrder::where('team_id', $id)->latest()->get();

        return view('module-maintenance-work-orders-livewire::livewire.work-order-list', compact('orders'));
    }

    private function orderForCurrentTeam(int $orderId): WorkOrder
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return WorkOrder::query()->where('team_id', $teamId)->findOrFail($orderId);
    }
}
