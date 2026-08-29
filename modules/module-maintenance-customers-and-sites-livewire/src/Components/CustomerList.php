<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Livewire\Component;

class CustomerList extends Component
{
    public string $name = '';

    public string $code = '';

    public string $email = '';

    public ?int $editingCustomerId = null;

    public function save(CreateCustomer $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'email' => 'nullable|email|max:255']);
        $create->handle((int) $id, ['name' => $this->name, 'code' => $this->code, 'email' => $this->email]);
        $this->reset(['name', 'code', 'email']);
        $this->dispatch('maintenance-customers-and-sites-customer-created');
    }

    public function edit(int $customerId): void
    {
        $customer = $this->customerForCurrentTeam($customerId);
        $this->editingCustomerId = $customer->getKey();
        $this->name = $customer->name;
        $this->code = $customer->code;
        $this->email = (string) ($customer->email ?? '');
    }

    public function update(UpdateCustomer $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingCustomerId === null, 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'email' => 'nullable|email|max:255']);
        $update->handle((int) $teamId, $this->customerForCurrentTeam($this->editingCustomerId), ['name' => $this->name, 'code' => $this->code, 'email' => $this->email]);
        $this->cancelEdit();
    }

    public function delete(int $customerId, DeleteCustomer $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->customerForCurrentTeam($customerId));
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'code', 'email', 'editingCustomerId']);
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $customers = $id === null ? collect() : Customer::where('team_id', $id)->orderBy('name')->get();

        return view('module-maintenance-customers-and-sites-livewire::livewire.customer-list', compact('customers'));
    }

    private function customerForCurrentTeam(int $customerId): Customer
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return Customer::query()->where('team_id', $teamId)->findOrFail($customerId);
    }
}
