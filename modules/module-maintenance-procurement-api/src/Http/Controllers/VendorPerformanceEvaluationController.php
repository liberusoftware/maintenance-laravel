<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Procurement\Actions\CreateVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Actions\DeleteVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Actions\UpdateVendorPerformanceEvaluation;
use Liberu\Modules\Maintenance\Procurement\Models\VendorPerformanceEvaluation;

class VendorPerformanceEvaluationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', VendorPerformanceEvaluation::class), 403);
        $query = VendorPerformanceEvaluation::query()->where('team_id', $teamId);
        if ($request->filled('vendor_name')) {
            $query->forVendor($request->string('vendor_name')->trim()->toString());
        }
        if ($request->boolean('high_performance')) {
            $query->highPerformance($request->float('threshold', 4.0));
        }
        $items = $query->latest('evaluation_date')->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (VendorPerformanceEvaluation $evaluation): array => $this->resource($evaluation))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateVendorPerformanceEvaluation $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', VendorPerformanceEvaluation::class), 403);
        $data = $request->validate(['vendor_name' => 'required|string|max:255', 'vendor_contract_id' => 'nullable|integer', 'evaluation_date' => 'required|date', 'quality_rating' => 'nullable|integer|min:0|max:5', 'timeliness_rating' => 'nullable|integer|min:0|max:5', 'communication_rating' => 'nullable|integer|min:0|max:5', 'cost_effectiveness_rating' => 'nullable|integer|min:0|max:5', 'professionalism_rating' => 'nullable|integer|min:0|max:5', 'strengths' => 'nullable|string|max:10000', 'areas_for_improvement' => 'nullable|string|max:10000', 'comments' => 'nullable|string|max:10000', 'would_recommend' => 'nullable|boolean']);
        $data['evaluated_by'] = $request->user()->getKey();

        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function show(Request $request, VendorPerformanceEvaluation $vendorPerformanceEvaluation): JsonResponse
    {
        abort_unless($this->teamId($request) === (int) $vendorPerformanceEvaluation->team_id && $request->user()->can('view', $vendorPerformanceEvaluation), 404);

        return response()->json(['data' => $this->resource($vendorPerformanceEvaluation)]);
    }

    public function update(Request $request, VendorPerformanceEvaluation $vendorPerformanceEvaluation, UpdateVendorPerformanceEvaluation $update): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $vendorPerformanceEvaluation->team_id && $request->user()->can('update', $vendorPerformanceEvaluation), 404);
        $data = $request->validate(['vendor_name' => 'sometimes|required|string|max:255', 'evaluation_date' => 'sometimes|required|date', 'quality_rating' => 'sometimes|integer|min:0|max:5', 'timeliness_rating' => 'sometimes|integer|min:0|max:5', 'communication_rating' => 'sometimes|integer|min:0|max:5', 'cost_effectiveness_rating' => 'sometimes|integer|min:0|max:5', 'professionalism_rating' => 'sometimes|integer|min:0|max:5', 'strengths' => 'sometimes|nullable|string|max:10000', 'areas_for_improvement' => 'sometimes|nullable|string|max:10000', 'comments' => 'sometimes|nullable|string|max:10000', 'would_recommend' => 'sometimes|boolean']);

        return response()->json(['data' => $this->resource($update->handle($teamId, $vendorPerformanceEvaluation, $data))]);
    }

    public function destroy(Request $request, VendorPerformanceEvaluation $vendorPerformanceEvaluation, DeleteVendorPerformanceEvaluation $delete): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($teamId === (int) $vendorPerformanceEvaluation->team_id && $request->user()->can('delete', $vendorPerformanceEvaluation), 404);
        $delete->handle($teamId, $vendorPerformanceEvaluation);

        return response()->json(null, 204);
    }

    private function teamId(Request $request): ?int
    {
        $id = $request->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function resource(VendorPerformanceEvaluation $evaluation): array
    {
        return ['id' => (string) $evaluation->getKey(), 'type' => 'maintenance-vendor-evaluation', 'attributes' => ['vendor_name' => $evaluation->vendor_name, 'vendor_contract_id' => $evaluation->vendor_contract_id, 'evaluation_date' => $evaluation->evaluation_date?->toDateString(), 'evaluated_by' => $evaluation->evaluated_by, 'quality_rating' => $evaluation->quality_rating, 'timeliness_rating' => $evaluation->timeliness_rating, 'communication_rating' => $evaluation->communication_rating, 'cost_effectiveness_rating' => $evaluation->cost_effectiveness_rating, 'professionalism_rating' => $evaluation->professionalism_rating, 'overall_rating' => $evaluation->overall_rating, 'strengths' => $evaluation->strengths, 'areas_for_improvement' => $evaluation->areas_for_improvement, 'comments' => $evaluation->comments, 'would_recommend' => $evaluation->would_recommend]];
    }
}
