<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderComment;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderDependency;
use Liberu\Modules\Maintenance\WorkOrders\Actions\AddWorkOrderEvidence;
use Liberu\Modules\Maintenance\WorkOrders\Actions\CreateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\DeleteWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Actions\RemoveWorkOrderDependency;
use Liberu\Modules\Maintenance\WorkOrders\Actions\RemoveWorkOrderEvidence;
use Liberu\Modules\Maintenance\WorkOrders\Actions\UpdateWorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderDependency;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrderEvidence;
use Livewire\Component;

class WorkOrderList extends Component
{
    public string $title = '';

    public string $description = '';

    public string $guestName = '';

    public string $guestEmail = '';

    public string $guestPhone = '';

    public string $notes = '';

    public ?int $editingOrderId = null;

    public string $comment = '';

    public bool $commentIsInternal = false;

    public ?int $dependsOnWorkOrderId = null;

    public string $evidenceKind = '';

    public string $evidenceLabel = '';

    public string $evidenceReference = '';

    public function save(CreateWorkOrder $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'guestName' => 'nullable|string|max:255', 'guestEmail' => 'nullable|email|max:255', 'guestPhone' => 'nullable|string|max:64', 'notes' => 'nullable|string|max:10000']);
        $create->handle((int) $id, ['title' => $this->title, 'description' => $this->description, 'guest_name' => $this->guestName ?: null, 'guest_email' => $this->guestEmail ?: null, 'guest_phone' => $this->guestPhone ?: null, 'submitted_at' => now(), 'notes' => $this->notes ?: null]);
        $this->reset(['title', 'description', 'guestName', 'guestEmail', 'guestPhone', 'notes']);
        $this->dispatch('maintenance-work-order-created');
    }

    public function edit(int $orderId): void
    {
        $order = $this->orderForCurrentTeam($orderId);
        $this->editingOrderId = $order->getKey();
        $this->title = $order->title;
        $this->description = (string) ($order->description ?? '');
        $this->guestName = (string) ($order->guest_name ?? '');
        $this->guestEmail = (string) ($order->guest_email ?? '');
        $this->guestPhone = (string) ($order->guest_phone ?? '');
        $this->notes = (string) ($order->notes ?? '');
    }

    public function update(UpdateWorkOrder $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingOrderId === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'guestName' => 'nullable|string|max:255', 'guestEmail' => 'nullable|email|max:255', 'guestPhone' => 'nullable|string|max:64', 'notes' => 'nullable|string|max:10000']);
        $update->handle((int) $teamId, $this->orderForCurrentTeam($this->editingOrderId), ['title' => $this->title, 'description' => $this->description, 'guest_name' => $this->guestName ?: null, 'guest_email' => $this->guestEmail ?: null, 'guest_phone' => $this->guestPhone ?: null, 'notes' => $this->notes ?: null]);
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
        $this->reset(['title', 'description', 'guestName', 'guestEmail', 'guestPhone', 'notes', 'editingOrderId']);
    }

    public function addDependency(int $orderId, AddWorkOrderDependency $add): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->dependsOnWorkOrderId === null, 403);
        $this->validate(['dependsOnWorkOrderId' => ['required', 'integer', 'min:1']]);
        $add->handle((int) $teamId, $this->orderForCurrentTeam($orderId), $this->orderForCurrentTeam($this->dependsOnWorkOrderId));
        $this->reset('dependsOnWorkOrderId');
    }

    public function removeDependency(int $dependencyId, RemoveWorkOrderDependency $remove): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $dependency = WorkOrderDependency::query()->where('team_id', $teamId)->findOrFail($dependencyId);
        $remove->handle((int) $teamId, $dependency);
    }

    public function addEvidence(int $orderId, AddWorkOrderEvidence $add): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['evidenceKind' => 'required|string|max:64', 'evidenceLabel' => 'required|string|max:255', 'evidenceReference' => 'required|string|max:10000']);
        $add->handle((int) $teamId, $this->orderForCurrentTeam($orderId), ['kind' => $this->evidenceKind, 'label' => $this->evidenceLabel, 'reference' => $this->evidenceReference, 'added_by' => auth()->id()]);
        $this->reset(['evidenceKind', 'evidenceLabel', 'evidenceReference']);
    }

    public function removeEvidence(int $evidenceId, RemoveWorkOrderEvidence $remove): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $evidence = WorkOrderEvidence::query()->where('team_id', $teamId)->findOrFail($evidenceId);
        $remove->handle((int) $teamId, $evidence);
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
        $orders = $id === null ? collect() : WorkOrder::where('team_id', $id)->with(['comments', 'dependencies.dependsOn', 'evidence'])->latest()->get();

        return view('module-maintenance-work-orders-livewire::livewire.work-order-list', compact('orders'));
    }

    private function orderForCurrentTeam(int $orderId): WorkOrder
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return WorkOrder::query()->where('team_id', $teamId)->findOrFail($orderId);
    }
}
