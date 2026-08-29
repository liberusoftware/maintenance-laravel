<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Commercial\Actions\CreateCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Actions\DeleteCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Actions\TransitionCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Actions\UpdateCommercialRecord;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;
use Livewire\Component;

class CommercialList extends Component
{
    public string $kind = '';

    public string $title = '';

    public ?int $editingRecordId = null;

    public function save(CreateCommercialRecord $create): void
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

    public function update(UpdateCommercialRecord $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingRecordId === null, 403);
        $this->validate(['kind' => 'required|string|max:80', 'title' => 'required|string|max:255']);
        $update->handle((int) $teamId, $this->recordForCurrentTeam($this->editingRecordId), ['kind' => $this->kind, 'title' => $this->title]);
        $this->cancelEdit();
    }

    public function delete(int $recordId, DeleteCommercialRecord $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->recordForCurrentTeam($recordId));
    }

    public function transition(int $recordId, string $status, TransitionCommercialRecord $transition): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['status' => 'required|in:proposed,approved,rejected,fulfilled,cancelled']);
        $transition->handle((int) $teamId, $this->recordForCurrentTeam($recordId), $status);
    }

    public function cancelEdit(): void
    {
        $this->reset(['kind', 'title', 'editingRecordId']);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $records = $teamId === null ? collect() : CommercialRecord::where('team_id', $teamId)->latest()->get();

        return view('module-maintenance-commercial-livewire::commercial-list', compact('records'));
    }

    private function recordForCurrentTeam(int $recordId): CommercialRecord
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return CommercialRecord::query()->where('team_id', $teamId)->findOrFail($recordId);
    }
}
