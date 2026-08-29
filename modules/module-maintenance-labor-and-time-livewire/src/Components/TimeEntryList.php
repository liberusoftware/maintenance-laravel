<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\DeleteTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\UpdateTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;
use Livewire\Component;

class TimeEntryList extends Component
{
    public string $description = '';

    public int $minutes = 1;

    public ?int $editingEntryId = null;

    public function save(CreateTimeEntry $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['description' => 'nullable|string|max:255', 'minutes' => 'required|integer|min:1']);
        $create->handle((int) $id, ['description' => $this->description, 'minutes' => $this->minutes, 'user_id' => auth()->id()]);
        $this->reset(['description', 'minutes']);
        $this->minutes = 1;
        $this->dispatch('maintenance-time-entry-created');
    }

    public function edit(int $entryId): void
    {
        $entry = $this->entryForCurrentTeam($entryId);
        $this->editingEntryId = $entry->getKey();
        $this->description = (string) ($entry->description ?? '');
        $this->minutes = (int) $entry->minutes;
    }

    public function update(UpdateTimeEntry $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingEntryId === null, 403);
        $this->validate(['description' => 'nullable|string|max:255', 'minutes' => 'required|integer|min:1']);
        $update->handle((int) $teamId, $this->entryForCurrentTeam($this->editingEntryId), ['description' => $this->description, 'minutes' => $this->minutes]);
        $this->cancelEdit();
    }

    public function delete(int $entryId, DeleteTimeEntry $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->entryForCurrentTeam($entryId));
    }

    public function cancelEdit(): void
    {
        $this->reset(['description', 'minutes', 'editingEntryId']);
        $this->minutes = 1;
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $entries = $id === null ? collect() : TimeEntry::where('team_id', $id)->latest()->get();

        return view('module-maintenance-labor-and-time-livewire::livewire.time-entry-list', compact('entries'));
    }

    private function entryForCurrentTeam(int $entryId): TimeEntry
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return TimeEntry::query()->where('team_id', $teamId)->findOrFail($entryId);
    }
}
