<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\CreateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\DeleteSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Actions\UpdateSite;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Customer;
use Liberu\Modules\Maintenance\CustomersAndSites\Models\Site;
use Livewire\Component;

class SiteList extends Component
{
    public string $name = '';

    public string $code = '';

    public ?int $customer_id = null;

    public ?int $editingSiteId = null;

    public function save(CreateSite $create): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['customer_id' => 'required|integer', 'name' => 'required|string|max:255', 'code' => 'required|string|max:64']);
        $create->handle((int) $teamId, ['customer_id' => $this->customer_id, 'name' => $this->name, 'code' => $this->code]);
        $this->reset(['customer_id', 'name', 'code']);
    }

    public function edit(int $siteId): void
    {
        $site = $this->siteForCurrentTeam($siteId);
        $this->editingSiteId = $site->getKey();
        $this->customer_id = $site->customer_id;
        $this->name = $site->name;
        $this->code = $site->code;
    }

    public function update(UpdateSite $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingSiteId === null, 403);
        $this->validate(['customer_id' => 'required|integer', 'name' => 'required|string|max:255', 'code' => 'required|string|max:64']);
        $update->handle((int) $teamId, $this->siteForCurrentTeam($this->editingSiteId), ['customer_id' => $this->customer_id, 'name' => $this->name, 'code' => $this->code]);
        $this->cancelEdit();
    }

    public function delete(int $siteId, DeleteSite $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->siteForCurrentTeam($siteId));
    }

    public function cancelEdit(): void
    {
        $this->reset(['customer_id', 'name', 'code', 'editingSiteId']);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $sites = $teamId === null ? collect() : Site::query()->where('team_id', $teamId)->with('customer')->orderBy('name')->get();
        $customers = $teamId === null ? collect() : Customer::query()->where('team_id', $teamId)->orderBy('name')->get();

        return view('module-maintenance-customers-and-sites-livewire::livewire.site-list', compact('sites', 'customers'));
    }

    private function siteForCurrentTeam(int $siteId): Site
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return Site::query()->where('team_id', $teamId)->findOrFail($siteId);
    }
}
