<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Actions\DeleteScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Actions\TransitionScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Actions\UpdateScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;
use Livewire\Component;

class ScheduleList extends Component
{
    public string $title = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $recurrence_type = '';

    public int $recurrence_value = 1;

    public ?int $editingEntryId = null;

    public function save(CreateScheduleEntry $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at']);
        $this->validate(['recurrence_type' => 'nullable|in:daily,weekly,monthly,yearly,hours', 'recurrence_value' => 'required|integer|min:1']);
        $create->handle((int) $id, ['title' => $this->title, 'starts_at' => $this->starts_at, 'ends_at' => $this->ends_at, 'recurrence_type' => $this->recurrence_type ?: null, 'recurrence_value' => $this->recurrence_value]);
        $this->reset(['title', 'starts_at', 'ends_at', 'recurrence_type', 'recurrence_value']);
        $this->dispatch('maintenance-schedule-created');
    }

    public function edit(int $entryId): void
    {
        $entry = $this->entryForCurrentTeam($entryId);
        $this->editingEntryId = $entry->getKey();
        $this->title = $entry->title;
        $this->starts_at = $entry->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $entry->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->recurrence_type = (string) ($entry->recurrence_type ?? '');
        $this->recurrence_value = (int) $entry->recurrence_value;
    }

    public function update(UpdateScheduleEntry $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingEntryId === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at', 'recurrence_type' => 'nullable|in:daily,weekly,monthly,yearly,hours', 'recurrence_value' => 'required|integer|min:1']);
        $update->handle((int) $teamId, $this->entryForCurrentTeam($this->editingEntryId), ['title' => $this->title, 'starts_at' => $this->starts_at, 'ends_at' => $this->ends_at, 'recurrence_type' => $this->recurrence_type ?: null, 'recurrence_value' => $this->recurrence_value]);
        $this->cancelEdit();
    }

    public function delete(int $entryId, DeleteScheduleEntry $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $delete->handle((int) $teamId, $this->entryForCurrentTeam($entryId));
    }

    public function transition(int $entryId, string $status, TransitionScheduleEntry $transition): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $transition->handle((int) $teamId, $this->entryForCurrentTeam($entryId), $status);
    }

    public function cancelEdit(): void
    {
        $this->reset(['title', 'starts_at', 'ends_at', 'recurrence_type', 'recurrence_value', 'editingEntryId']);
        $this->recurrence_value = 1;
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $entries = $id === null ? collect() : ScheduleEntry::where('team_id', $id)->orderBy('starts_at')->get();

        return view('module-maintenance-scheduling-livewire::livewire.schedule-list', compact('entries'));
    }

    private function entryForCurrentTeam(int $entryId): ScheduleEntry
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return ScheduleEntry::query()->where('team_id', $teamId)->findOrFail($entryId);
    }
}
