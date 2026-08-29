<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\CreateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\DeleteMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Actions\UpdateMaintenancePlan;
use Liberu\Modules\Maintenance\PreventativeMaintenance\Models\MaintenancePlan;
use Livewire\Component;

class MaintenancePlanList extends Component
{
    public string $name = '';

    public string $code = '';

    public int $frequency_value = 1;

    public ?int $editingPlanId = null;

    public function save(CreateMaintenancePlan $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'frequency_value' => 'required|integer|min:1']);
        $create->handle((int) $id, ['name' => $this->name, 'code' => $this->code, 'frequency_value' => $this->frequency_value]);
        $this->reset(['name', 'code']);
        $this->dispatch('maintenance-plan-created');
    }

    public function edit(int $planId): void
    {
        $plan = $this->planForCurrentTeam($planId);
        $this->editingPlanId = $plan->getKey();
        $this->name = $plan->name;
        $this->code = $plan->code;
        $this->frequency_value = (int) $plan->frequency_value;
    }

    public function update(UpdateMaintenancePlan $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingPlanId === null, 403);
        $this->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:64', 'frequency_value' => 'required|integer|min:1']);
        $update->handle((int) $teamId, $this->planForCurrentTeam($this->editingPlanId), ['name' => $this->name, 'code' => $this->code, 'frequency_value' => $this->frequency_value]);
        $this->cancelEdit();
    }

    public function delete(int $planId, DeleteMaintenancePlan $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->planForCurrentTeam($planId));
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'code', 'frequency_value', 'editingPlanId']);
        $this->frequency_value = 1;
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $plans = $id === null ? collect() : MaintenancePlan::where('team_id', $id)->orderBy('name')->get();

        return view('module-maintenance-preventative-maintenance-livewire::livewire.plan-list', compact('plans'));
    }

    private function planForCurrentTeam(int $planId): MaintenancePlan
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return MaintenancePlan::query()->where('team_id', $teamId)->findOrFail($planId);
    }
}
