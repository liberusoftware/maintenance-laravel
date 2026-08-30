<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Procurement\Actions\CreateVendorContract;
use Liberu\Modules\Maintenance\Procurement\Actions\DeleteVendorContract;
use Liberu\Modules\Maintenance\Procurement\Actions\TransitionVendorContract;
use Liberu\Modules\Maintenance\Procurement\Models\VendorContract;
use Livewire\Component;

class VendorContractList extends Component
{
    public string $vendor_name = '';

    public string $contract_number = '';

    public string $title = '';

    public string $start_date = '';

    public string $end_date = '';

    public ?int $editingContractId = null;

    public function save(CreateVendorContract $create): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['vendor_name' => 'required|string|max:255', 'contract_number' => 'required|string|max:255', 'title' => 'required|string|max:255', 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date']);
        $create->handle((int) $teamId, ['vendor_name' => $this->vendor_name, 'contract_number' => $this->contract_number, 'title' => $this->title, 'start_date' => $this->start_date, 'end_date' => $this->end_date]);
        $this->reset(['vendor_name', 'contract_number', 'title', 'start_date', 'end_date']);
        $this->dispatch('maintenance-vendor-contract-created');
    }

    public function delete(int $contractId, DeleteVendorContract $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->contractForCurrentTeam($contractId));
    }

    public function transition(int $contractId, string $status, TransitionVendorContract $transition): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['status' => 'required|in:active,expired,terminated,renewed']);
        $transition->handle((int) $teamId, $this->contractForCurrentTeam($contractId), $status);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $contracts = $teamId === null ? collect() : VendorContract::query()->where('team_id', $teamId)->orderBy('end_date')->get();

        return view('module-maintenance-procurement-livewire::livewire.vendor-contract-list', compact('contracts'));
    }

    private function contractForCurrentTeam(int $contractId): VendorContract
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return VendorContract::query()->where('team_id', $teamId)->findOrFail($contractId);
    }
}
