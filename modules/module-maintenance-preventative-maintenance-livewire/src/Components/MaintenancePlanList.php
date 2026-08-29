<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CreateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;
use Livewire\Component;

class MaintenancePlanList extends Component
{
    public string $name = '';

    public string $code = '';

    public int $frequency_value = 1;

    public function save(CreateMaintenancePlan $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'frequency_value' => 'required|integer|min:1']);
        $create->handle((int) $id, ['name' => $this->name, 'code' => $this->code, 'frequency_value' => $this->frequency_value]);
        $this->reset(['name', 'code']);
        $this->dispatch('maintenance-plan-created');
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $plans = $id === null ? collect() : MaintenancePlan::where('team_id', $id)->orderBy('name')->get();

        return view('module-maintenance-preventative-maintenance-livewire::livewire.plan-list', compact('plans'));
    }
}
