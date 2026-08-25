<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Reporting\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Report\Actions\CreateReportRecord;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;
use Livewire\Component;

class ReportingList extends Component
{
    public string $kind = '';

    public string $title = '';

    public function save(CreateReportRecord $create): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['kind' => 'required|string|max:80', 'title' => 'required|string|max:255']);
        $create->handle((int) $teamId, ['kind' => $this->kind, 'title' => $this->title]);
        $this->reset(['kind', 'title']);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $records = $teamId === null ? collect() : ReportRecord::where('team_id', $teamId)->latest()->get();

        return view('module-maintenance-reporting-livewire::reporting-list', compact('records'));
    }
}
