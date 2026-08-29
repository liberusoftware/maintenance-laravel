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

final class InspectionList extends Component
{
    public string $title = '';

    public string $templateKey = '';

    public string $outcome = 'pass';

    public ?int $editingInspectionId = null;

    public function save(CreateInspection $create): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'templateKey' => 'nullable|string|max:255']);
        $create->handle((int) $teamId, ['title' => $this->title, 'template_key' => $this->templateKey ?: null]);
        $this->reset(['title', 'templateKey']);
        $this->dispatch('maintenance-inspection-created');
    }

    public function edit(int $inspectionId): void
    {
        $inspection = $this->inspectionForCurrentTeam($inspectionId);
        $this->editingInspectionId = $inspection->getKey();
        $this->title = $inspection->title;
        $this->templateKey = (string) ($inspection->template_key ?? '');
    }

    public function update(UpdateInspection $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingInspectionId === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'templateKey' => 'nullable|string|max:255']);
        $update->handle((int) $teamId, $this->inspectionForCurrentTeam($this->editingInspectionId), ['title' => $this->title, 'template_key' => $this->templateKey ?: null]);
        $this->cancelEdit();
    }

    public function complete(int $inspectionId, CompleteInspection $complete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $complete->handle((int) $teamId, $this->inspectionForCurrentTeam($inspectionId), $this->outcome);
        $this->reset('outcome');
    }

    public function delete(int $inspectionId, DeleteInspection $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->inspectionForCurrentTeam($inspectionId));
    }

    public function cancelEdit(): void
    {
        $this->reset(['title', 'templateKey', 'editingInspectionId']);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $inspections = $teamId === null ? collect() : Inspection::query()->where('team_id', $teamId)->latest()->get();

        return view('module-maintenance-inspections-livewire::livewire.inspection-list', compact('inspections'));
    }

    private function inspectionForCurrentTeam(int $inspectionId): Inspection
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return Inspection::query()->where('team_id', $teamId)->findOrFail($inspectionId);
    }
}
