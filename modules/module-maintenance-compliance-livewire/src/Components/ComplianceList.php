<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Actions\DeleteComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Actions\UpdateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;
use Livewire\Component;

class ComplianceList extends Component
{
    public string $kind = '';

    public string $title = '';

    public ?int $editingRecordId = null;

    public function save(CreateComplianceRecord $create): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['kind' => 'required|string|max:80', 'title' => 'required|string|max:255']);
        $create->handle((int) $teamId, ['kind' => $this->kind, 'title' => $this->title]);
        $this->reset(['kind', 'title']);
    }

    public function edit(int $recordId): void
    {
        $record = $this->recordForCurrentTeam($recordId);
        $this->editingRecordId = $record->getKey();
        $this->kind = $record->kind;
        $this->title = $record->title;
    }

    public function update(UpdateComplianceRecord $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingRecordId === null, 403);
        $this->validate(['kind' => 'required|string|max:80', 'title' => 'required|string|max:255']);
        $update->handle((int) $teamId, $this->recordForCurrentTeam($this->editingRecordId), ['kind' => $this->kind, 'title' => $this->title]);
        $this->cancelEdit();
    }

    public function delete(int $recordId, DeleteComplianceRecord $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->recordForCurrentTeam($recordId));
    }

    public function cancelEdit(): void
    {
        $this->reset(['kind', 'title', 'editingRecordId']);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $records = $teamId === null ? collect() : ComplianceRecord::where('team_id', $teamId)->latest()->get();

        return view('module-maintenance-compliance-livewire::compliance-list', compact('records'));
    }

    private function recordForCurrentTeam(int $recordId): ComplianceRecord
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return ComplianceRecord::query()->where('team_id', $teamId)->findOrFail($recordId);
    }
}
