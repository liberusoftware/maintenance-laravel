<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Reporting\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Report\Actions\CreateReportRecord;
use Liberu\Modules\Maintenance\Report\Actions\DeleteReportRecord;
use Liberu\Modules\Maintenance\Report\Actions\PublishReport;
use Liberu\Modules\Maintenance\Report\Actions\UpdateReportRecord;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;
use Livewire\Component;

class ReportingList extends Component
{
    public string $kind = '';

    public string $title = '';

    public ?int $editingRecordId = null;

    public function save(CreateReportRecord $create): void
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

    public function update(UpdateReportRecord $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingRecordId === null, 403);
        $this->validate(['kind' => 'required|string|max:80', 'title' => 'required|string|max:255']);
        $update->handle((int) $teamId, $this->recordForCurrentTeam($this->editingRecordId), ['kind' => $this->kind, 'title' => $this->title]);
        $this->cancelEdit();
    }

    public function delete(int $recordId, DeleteReportRecord $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->recordForCurrentTeam($recordId));
    }

    public function publish(int $recordId, PublishReport $publish): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $publish->execute((int) $teamId, $this->recordForCurrentTeam($recordId));
    }

    public function cancelEdit(): void
    {
        $this->reset(['kind', 'title', 'editingRecordId']);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $records = $teamId === null ? collect() : ReportRecord::where('team_id', $teamId)->latest()->get();

        return view('module-maintenance-reporting-livewire::reporting-list', compact('records'));
    }

    private function recordForCurrentTeam(int $recordId): ReportRecord
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return ReportRecord::query()->where('team_id', $teamId)->findOrFail($recordId);
    }
}
