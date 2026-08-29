<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspection;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;
use Livewire\Component;

class InspectionList extends Component
{
    public string $title = '';

    public string $template_key = '';

    public function save(CreateInspection $create): void
    {
        $id = auth()->user()?->currentTeam?->getKey();
        abort_if($id === null, 403);
        $this->validate(['title' => 'required|string|max:255', 'template_key' => 'nullable|string|max:255']);
        $create->handle((int) $id, ['title' => $this->title, 'template_key' => $this->template_key]);
        $this->reset(['title', 'template_key']);
        $this->dispatch('maintenance-inspection-created');
    }

    public function render(): View
    {
        $id = auth()->user()?->currentTeam?->getKey();
        $inspections = $id === null ? collect() : Inspection::where('team_id', $id)->latest()->get();

        return view('module-maintenance-inspections-livewire::livewire.inspection-list', compact('inspections'));
    }
}
