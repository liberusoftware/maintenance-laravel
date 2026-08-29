<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;
use Livewire\Component;

class ScheduleList extends Component
{
    public string $title = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public function save(CreateScheduleEntry $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at']);
        $create->handle((int) $id, ['title' => $this->title, 'starts_at' => $this->starts_at, 'ends_at' => $this->ends_at]);
        $this->reset(['title', 'starts_at', 'ends_at']);
        $this->dispatch('maintenance-schedule-created');
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $entries = $id === null ? collect() : ScheduleEntry::where('team_id', $id)->orderBy('starts_at')->get();

        return view('module-maintenance-scheduling-livewire::livewire.schedule-list', compact('entries'));
    }
}
