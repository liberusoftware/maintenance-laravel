<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\LaborAndTime\Actions\CreateTimeEntry;
use Liberu\Modules\Maintenance\LaborAndTime\Models\TimeEntry;
use Livewire\Component;

class TimeEntryList extends Component
{
    public string $description = '';

    public int $minutes = 1;

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

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $entries = $id === null ? collect() : TimeEntry::where('team_id', $id)->latest()->get();

        return view('module-maintenance-labor-and-time-livewire::livewire.time-entry-list', compact('entries'));
    }
}
