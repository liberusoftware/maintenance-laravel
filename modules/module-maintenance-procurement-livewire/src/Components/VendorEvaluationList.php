<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Procurement\Actions\CreateVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Actions\DeleteVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Models\VendorPerformanceEvaluation;
use Livewire\Component;

class VendorEvaluationList extends Component
{
    public string $vendor_name = '';

    public string $evaluation_date = '';

    public int $quality_rating = 0;

    public int $timeliness_rating = 0;

    public int $communication_rating = 0;

    public int $cost_effectiveness_rating = 0;

    public int $professionalism_rating = 0;

    public function save(CreateVendorPerformanceEvaluation $create): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['vendor_name' => 'required|string|max:255', 'evaluation_date' => 'required|date', 'quality_rating' => 'integer|min:0|max:5', 'timeliness_rating' => 'integer|min:0|max:5', 'communication_rating' => 'integer|min:0|max:5', 'cost_effectiveness_rating' => 'integer|min:0|max:5', 'professionalism_rating' => 'integer|min:0|max:5']);
        $create->handle((int) $teamId, ['vendor_name' => $this->vendor_name, 'evaluation_date' => $this->evaluation_date, 'quality_rating' => $this->quality_rating, 'timeliness_rating' => $this->timeliness_rating, 'communication_rating' => $this->communication_rating, 'cost_effectiveness_rating' => $this->cost_effectiveness_rating, 'professionalism_rating' => $this->professionalism_rating, 'evaluated_by' => auth()->id()]);
        $this->reset();
        $this->dispatch('maintenance-vendor-evaluation-created');
    }

    public function delete(int $evaluationId, DeleteVendorPerformanceEvaluation $delete): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $evaluation = VendorPerformanceEvaluation::query()->where('team_id', $teamId)->findOrFail($evaluationId);
        $delete->handle((int) $teamId, $evaluation);
    }

    public function render(): View
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        $evaluations = $teamId === null ? collect() : VendorPerformanceEvaluation::query()->where('team_id', $teamId)->latest('evaluation_date')->get();

        return view('module-maintenance-procurement-livewire::livewire.vendor-evaluation-list', compact('evaluations'));
    }
}
