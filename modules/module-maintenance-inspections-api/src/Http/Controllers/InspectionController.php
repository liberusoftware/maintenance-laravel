<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Inspections\Actions\CompleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\DeleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\UpdateInspection;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

final class InspectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', Inspection::class), 403);
        $query = Inspection::query()->where('team_id', $teamId);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('outcome')) {
            $query->withOutcome($request->string('outcome')->toString());
        }
        $items = $query->latest()->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (Inspection $inspection): array => $this->resource($inspection))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateInspection $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', Inspection::class), 403);

        return response()->json(['data' => $this->resource($create->handle($teamId, $request->validate($this->rules())))], 201);
    }

    public function show(Request $request, Inspection $inspection): JsonResponse
    {
        $this->authorizeRecord($request, $inspection, 'view');

        return response()->json(['data' => $this->resource($inspection)]);
    }

    public function update(Request $request, Inspection $inspection, UpdateInspection $update): JsonResponse
    {
        $this->authorizeRecord($request, $inspection, 'update');
        $rules = $this->rules();
        foreach ($rules as $key => $rule) {
            $rules[$key] = 'sometimes|'.str_replace('required|', '', $rule);
        }

        return response()->json(['data' => $this->resource($update->handle((int) $inspection->team_id, $inspection, $request->validate($rules)))]);
    }

    public function complete(Request $request, Inspection $inspection, CompleteInspection $complete): JsonResponse
    {
        $this->authorizeRecord($request, $inspection, 'update');
        $data = $request->validate(['outcome' => ['required', 'in:pass,fail,conditional']]);

        return response()->json(['data' => $this->resource($complete->handle((int) $inspection->team_id, $inspection, $data['outcome']))]);
    }

    public function destroy(Request $request, Inspection $inspection, DeleteInspection $delete): JsonResponse
    {
        $this->authorizeRecord($request, $inspection, 'delete');
        $delete->handle((int) $inspection->team_id, $inspection);

        return response()->noContent();
    }

    private function teamId(Request $request): ?int
    {
        $id = $request->user()?->currentTeam?->getKey();

        return $id === null ? null : (int) $id;
    }

    private function authorizeRecord(Request $request, Inspection $inspection, string $ability): void
    {
        abort_unless($this->teamId($request) === (int) $inspection->team_id && $request->user()->can($ability, $inspection), 404);
    }

    /** @return array<string, mixed> */
    private function resource(Inspection $inspection): array
    {
        return ['id' => (string) $inspection->getKey(), 'type' => 'maintenance-inspection', 'attributes' => ['title' => $inspection->title, 'template_key' => $inspection->template_key, 'status' => $inspection->status, 'outcome' => $inspection->outcome, 'inspected_at' => $inspection->inspected_at?->toISOString(), 'inspector_id' => $inspection->inspector_id, 'readings' => $inspection->readings, 'failures' => $inspection->failures, 'signature' => $inspection->signature, 'certificate' => $inspection->certificate, 'follow_up' => $inspection->follow_up]];
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return ['title' => 'required|string|max:255', 'template_key' => 'nullable|string|max:255', 'inspector_id' => 'nullable|integer|min:1', 'inspected_at' => 'nullable|date', 'readings' => 'nullable|array', 'failures' => 'nullable|array', 'signature' => 'nullable|string|max:10000', 'certificate' => 'nullable|string|max:255', 'follow_up' => 'nullable|array'];
    }
}
