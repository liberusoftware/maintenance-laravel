<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Inspections\Actions\CompleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\DeleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\UpdateInspection;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;
use Livewire\Component;

class InspectionList extends Component
{
    public string $title = '';

    public string $template_key = '';

    public ?int $editingInspectionId = null;

    public function save(CreateInspection $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'template_key' => 'nullable|string|max:255']);
        $create->handle((int) $id, ['title' => $this->title, 'template_key' => $this->template_key]);
        $this->reset(['title', 'template_key']);
        $this->dispatch('maintenance-inspection-created');
    }

    public function edit(int $inspectionId): void
    {
        $inspection = $this->inspectionForCurrentTeam($inspectionId);
        $this->editingInspectionId = $inspection->getKey();
        $this->title = $inspection->title;
        $this->template_key = (string) ($inspection->template_key ?? '');
    }

    public function update(UpdateInspection $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingInspectionId === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'template_key' => 'nullable|string|max:255']);
        $update->handle((int) $teamId, $this->inspectionForCurrentTeam($this->editingInspectionId), ['title' => $this->title, 'template_key' => $this->template_key]);
        $this->cancelEdit();
    }

    public function delete(int $inspectionId, DeleteInspection $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->inspectionForCurrentTeam($inspectionId));
    }

    public function complete(int $inspectionId, string $outcome, CompleteInspection $complete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['outcome' => 'required|in:pass,fail,conditional']);
        $complete->handle((int) $teamId, $this->inspectionForCurrentTeam($inspectionId), $outcome);
    }

    public function cancelEdit(): void
    {
        $this->reset(['title', 'template_key', 'editingInspectionId']);
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $inspections = $id === null ? collect() : Inspection::where('team_id', $id)->latest()->get();

        return view('module-maintenance-inspections-livewire::livewire.inspection-list', compact('inspections'));
    }

    private function inspectionForCurrentTeam(int $inspectionId): Inspection
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return Inspection::query()->where('team_id', $teamId)->findOrFail($inspectionId);
    }
}
