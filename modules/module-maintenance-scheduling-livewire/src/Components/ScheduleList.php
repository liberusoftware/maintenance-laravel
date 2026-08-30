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

    public string $description = '';

    public string $instructions = '';

    public string $equipment_id = '';

    public string $assigned_to = '';

    public string $checklist_id = '';

    public string $estimated_duration = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $recurrence_type = '';

    public int $recurrence_value = 1;

    public ?int $editingEntryId = null;

    public function save(CreateScheduleEntry $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'instructions' => 'nullable|string|max:10000', 'equipment_id' => 'nullable|integer|min:1', 'assigned_to' => 'nullable|integer|min:1', 'checklist_id' => 'nullable|integer|min:1', 'estimated_duration' => 'nullable|integer|min:0', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at']);
        $this->validate(['recurrence_type' => 'nullable|in:daily,weekly,monthly,yearly,hours', 'recurrence_value' => 'required|integer|min:1']);
        $create->handle((int) $id, ['title' => $this->title, 'description' => $this->description, 'instructions' => $this->instructions, 'equipment_id' => $this->equipment_id !== '' ? (int) $this->equipment_id : null, 'assigned_to' => $this->assigned_to !== '' ? (int) $this->assigned_to : null, 'checklist_id' => $this->checklist_id !== '' ? (int) $this->checklist_id : null, 'estimated_duration' => $this->estimated_duration !== '' ? (int) $this->estimated_duration : null, 'starts_at' => $this->starts_at, 'ends_at' => $this->ends_at, 'recurrence_type' => $this->recurrence_type ?: null, 'recurrence_value' => $this->recurrence_value]);
        $this->reset(['title', 'description', 'instructions', 'equipment_id', 'assigned_to', 'checklist_id', 'estimated_duration', 'starts_at', 'ends_at', 'recurrence_type', 'recurrence_value']);
        $this->dispatch('maintenance-schedule-created');
    }

    public function edit(int $entryId): void
    {
        $entry = $this->entryForCurrentTeam($entryId);
        $this->editingEntryId = $entry->getKey();
        $this->title = $entry->title;
        $this->description = (string) ($entry->description ?? '');
        $this->instructions = (string) ($entry->instructions ?? '');
        $this->equipment_id = $entry->equipment_id === null ? '' : (string) $entry->equipment_id;
        $this->assigned_to = $entry->assigned_to === null ? '' : (string) $entry->assigned_to;
        $this->checklist_id = $entry->checklist_id === null ? '' : (string) $entry->checklist_id;
        $this->estimated_duration = $entry->estimated_duration === null ? '' : (string) $entry->estimated_duration;
        $this->starts_at = $entry->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $entry->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->recurrence_type = (string) ($entry->recurrence_type ?? '');
        $this->recurrence_value = (int) $entry->recurrence_value;
    }

    public function update(UpdateScheduleEntry $update): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null || $this->editingEntryId === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'instructions' => 'nullable|string|max:10000', 'equipment_id' => 'nullable|integer|min:1', 'assigned_to' => 'nullable|integer|min:1', 'checklist_id' => 'nullable|integer|min:1', 'estimated_duration' => 'nullable|integer|min:0', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at', 'recurrence_type' => 'nullable|in:daily,weekly,monthly,yearly,hours', 'recurrence_value' => 'required|integer|min:1']);
        $update->handle((int) $teamId, $this->entryForCurrentTeam($this->editingEntryId), ['title' => $this->title, 'description' => $this->description, 'instructions' => $this->instructions, 'equipment_id' => $this->equipment_id !== '' ? (int) $this->equipment_id : null, 'assigned_to' => $this->assigned_to !== '' ? (int) $this->assigned_to : null, 'checklist_id' => $this->checklist_id !== '' ? (int) $this->checklist_id : null, 'estimated_duration' => $this->estimated_duration !== '' ? (int) $this->estimated_duration : null, 'starts_at' => $this->starts_at, 'ends_at' => $this->ends_at, 'recurrence_type' => $this->recurrence_type ?: null, 'recurrence_value' => $this->recurrence_value]);
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
        $this->reset(['title', 'description', 'instructions', 'equipment_id', 'assigned_to', 'checklist_id', 'estimated_duration', 'starts_at', 'ends_at', 'recurrence_type', 'recurrence_value', 'editingEntryId']);
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
