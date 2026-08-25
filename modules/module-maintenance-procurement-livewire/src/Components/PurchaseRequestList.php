<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Procurement\Actions\CreatePurchaseRequest;
use Liberu\Modules\Maintenance\Procurement\Models\PurchaseRequest;
use Livewire\Component;

class PurchaseRequestList extends Component
{
    public string $title = '';

    public string $amount = '0';

    public function save(CreatePurchaseRequest $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'amount' => 'required|numeric|min:0']);
        $create->handle((int) $id, ['title' => $this->title, 'amount' => $this->amount, 'requested_by' => auth()->id()]);
        $this->reset(['title', 'amount']);
        $this->dispatch('maintenance-purchase-request-created');
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $requests = $id === null ? collect() : PurchaseRequest::where('team_id', $id)->latest()->get();

        return view('module-maintenance-procurement-livewire::livewire.purchase-request-list', compact('requests'));
    }
}
