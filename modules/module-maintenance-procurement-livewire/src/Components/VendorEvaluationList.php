<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Livewire\Components;

use Illuminate\View\View;
use Liberu\Modules\Maintenance\Procurement\Actions\CreateVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Actions\DeleteVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Actions\UpdateVendorPerformanceEvaluation;
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

    public ?int $editingEvaluationId = null;

    public function save(CreateVendorPerformanceEvaluation $create): void
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        $this->validate(['vendor_name' => 'required|string|max:255', 'evaluation_date' => 'required|date', 'quality_rating' => 'integer|min:0|max:5', 'timeliness_rating' => 'integer|min:0|max:5', 'communication_rating' => 'integer|min:0|max:5', 'cost_effectiveness_rating' => 'integer|min:0|max:5', 'professionalism_rating' => 'integer|min:0|max:5']);
        $attributes = ['vendor_name' => $this->vendor_name, 'evaluation_date' => $this->evaluation_date, 'quality_rating' => $this->quality_rating, 'timeliness_rating' => $this->timeliness_rating, 'communication_rating' => $this->communication_rating, 'cost_effectiveness_rating' => $this->cost_effectiveness_rating, 'professionalism_rating' => $this->professionalism_rating, 'evaluated_by' => auth()->id()];
        if ($this->editingEvaluationId === null) {
            $create->handle((int) $teamId, $attributes);
        } else {
            app(UpdateVendorPerformanceEvaluation::class)->handle((int) $teamId, $this->evaluationForCurrentTeam($this->editingEvaluationId), $attributes);
        }
        $this->reset();
        $this->dispatch('maintenance-vendor-evaluation-created');
    }

    public function edit(int $evaluationId): void
    {
        $evaluation = $this->evaluationForCurrentTeam($evaluationId);
        $this->editingEvaluationId = $evaluation->getKey();
        $this->vendor_name = $evaluation->vendor_name;
        $this->evaluation_date = $evaluation->evaluation_date?->toDateString() ?? '';
        $this->quality_rating = (int) $evaluation->quality_rating;
        $this->timeliness_rating = (int) $evaluation->timeliness_rating;
        $this->communication_rating = (int) $evaluation->communication_rating;
        $this->cost_effectiveness_rating = (int) $evaluation->cost_effectiveness_rating;
        $this->professionalism_rating = (int) $evaluation->professionalism_rating;
    }

    public function cancelEdit(): void
    {
        $this->reset();
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

    private function evaluationForCurrentTeam(int $evaluationId): VendorPerformanceEvaluation
    {
        $teamId = auth()->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return VendorPerformanceEvaluation::query()->where('team_id', $teamId)->findOrFail($evaluationId);
    }
}
