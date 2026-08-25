<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateCustomer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Livewire\Component;

class CustomerList extends Component
{
    public string $name = '';

    public string $code = '';

    public string $email = '';

    public function save(CreateCustomer $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'email' => 'nullable|email|max:255']);
        $create->handle((int) $id, ['name' => $this->name, 'code' => $this->code, 'email' => $this->email]);
        $this->reset(['name', 'code', 'email']);
        $this->dispatch('maintenance-customers-and-sites-customer-created');
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $customers = $id === null ? collect() : Customer::where('team_id', $id)->orderBy('name')->get();

        return view('module-maintenance-customers-and-sites-livewire::livewire.customer-list', compact('customers'));
    }
}
