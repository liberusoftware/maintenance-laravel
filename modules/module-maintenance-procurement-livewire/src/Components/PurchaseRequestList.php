<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Procurement\Actions\CreatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\DeletePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Actions\UpdatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;
use Livewire\Component;

class PurchaseRequestList extends Component
{
    public string $title = '';

    public string $amount = '0';

    public ?int $editingRequestId = null;

    public function save(CreatePurchaseRequest $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'amount' => 'required|numeric|min:0']);
        $create->handle((int) $id, ['title' => $this->title, 'amount' => $this->amount, 'requested_by' => auth()->id()]);
        $this->reset(['title', 'amount']);
        $this->dispatch('maintenance-purchase-request-created');
    }

    public function edit(int $requestId): void
    {
        $request = $this->requestForCurrentTeam($requestId);
        $this->editingRequestId = $request->getKey();
        $this->title = $request->title;
        $this->amount = (string) $request->amount;
    }

    public function update(UpdatePurchaseRequest $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingRequestId === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'amount' => 'required|numeric|min:0']);
        $update->handle((int) $teamId, $this->requestForCurrentTeam($this->editingRequestId), ['title' => $this->title, 'amount' => $this->amount]);
        $this->cancelEdit();
    }

    public function delete(int $requestId, DeletePurchaseRequest $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->requestForCurrentTeam($requestId));
    }

    public function cancelEdit(): void
    {
        $this->reset(['title', 'amount', 'editingRequestId']);
        $this->amount = '0';
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $requests = $id === null ? collect() : PurchaseRequest::where('team_id', $id)->latest()->get();

        return view('module-maintenance-procurement-livewire::livewire.purchase-request-list', compact('requests'));
    }

    private function requestForCurrentTeam(int $requestId): PurchaseRequest
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return PurchaseRequest::query()->where('team_id', $teamId)->findOrFail($requestId);
    }
}
