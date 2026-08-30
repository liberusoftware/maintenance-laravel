<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Inspections\Actions\CompleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\CompleteInspectionFollowUp;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspectionFollowUp;
use Liberu\Modules\Maintenance\Inspections\Actions\CreateInspectionTemplate;
use Liberu\Modules\Maintenance\Inspections\Actions\AddInspectionTemplateItem;
use Liberu\Modules\Maintenance\Inspections\Actions\UpdateInspectionTemplateItem;
use Liberu\Modules\Maintenance\Inspections\Actions\RemoveInspectionTemplateItem;
use Liberu\Modules\Maintenance\Inspections\Actions\DuplicateInspectionTemplate;
use Liberu\Modules\Maintenance\Inspections\Actions\DeleteInspection;
use Liberu\Modules\Maintenance\Inspections\Actions\UpdateInspection;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionTemplate;
use Liberu\Modules\Maintenance\Inspections\Models\InspectionFollowUp;

final class InspectionController extends Controller
{
    public function templates(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', Inspection::class), 403);

        return response()->json(['data' => InspectionTemplate::query()->where('team_id', $teamId)->where('is_active', true)->orderBy('name')->get()->map(fn (InspectionTemplate $template): array => $this->templateResource($template))->values()]);
    }

    public function storeTemplate(Request $request, CreateInspectionTemplate $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', InspectionTemplate::class), 403);
        $data = $request->validate(['key' => ['required', 'string', 'max:120'], 'name' => ['required', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:10000'], 'checklist' => ['present', 'array'], 'is_active' => ['sometimes', 'boolean']]);

        return response()->json(['data' => $this->templateResource($create->handle($teamId, $data))], 201);
    }

    public function storeTemplateItem(Request $request, string $template, AddInspectionTemplateItem $add): JsonResponse
    {
        $record = $this->templateForCurrentTeam($request, $template);
        abort_unless($request->user()->can('update', $record), 403);
        $data = $request->validate(['key' => ['required', 'string', 'max:120'], 'type' => ['sometimes', 'string'], 'required' => ['sometimes', 'boolean'], 'options' => ['sometimes', 'array'], 'when' => ['sometimes', 'array'], 'condition' => ['sometimes', 'array']]);
        $key = $data['key']; unset($data['key']);
        return response()->json(['data' => $this->templateResource($add->handle($this->teamId($request), $record, $key, $data))], 201);
    }

    public function updateTemplateItem(Request $request, string $template, string $item, UpdateInspectionTemplateItem $update): JsonResponse
    {
        $record = $this->templateForCurrentTeam($request, $template);
        abort_unless($request->user()->can('update', $record), 403);
        return response()->json(['data' => $this->templateResource($update->handle($this->teamId($request), $record, $item, $request->validate(['type' => ['sometimes', 'string'], 'required' => ['sometimes', 'boolean'], 'options' => ['sometimes', 'array'], 'when' => ['sometimes', 'array'], 'condition' => ['sometimes', 'array']]))) ]);
    }

    public function destroyTemplateItem(Request $request, string $template, string $item, RemoveInspectionTemplateItem $remove): JsonResponse
    {
        $record = $this->templateForCurrentTeam($request, $template);
        abort_unless($request->user()->can('update', $record), 403);
        $remove->handle($this->teamId($request), $record, $item);
        return response()->json(null, 204);
    }

    public function duplicateTemplate(Request $request, string $template, DuplicateInspectionTemplate $duplicate): JsonResponse
    {
        $record = $this->templateForCurrentTeam($request, $template);
        abort_unless($request->user()->can('create', InspectionTemplate::class), 403);
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:255'], 'key' => ['sometimes', 'string', 'max:120']]);
        return response()->json(['data' => $this->templateResource($duplicate->handle($this->teamId($request), $record, $data['name'] ?? null, $data['key'] ?? null))], 201);
    }

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

    public function followUps(Request $request, string $inspection): JsonResponse
    {
        $inspection = $this->inspectionForCurrentTeam($request, $inspection);
        $this->authorizeRecord($request, $inspection, 'view');

        return response()->json(['data' => $inspection->followUps()->latest()->get()->map(fn (InspectionFollowUp $followUp): array => $this->followUpResource($followUp))->values()]);
    }

    public function storeFollowUp(Request $request, string $inspection, CreateInspectionFollowUp $create): JsonResponse
    {
        $inspection = $this->inspectionForCurrentTeam($request, $inspection);
        $this->authorizeRecord($request, $inspection, 'update');
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'], 'assigned_to' => ['nullable', 'integer'], 'due_at' => ['nullable', 'date', 'after_or_equal:now']]);

        return response()->json(['data' => $this->followUpResource($create->handle((int) $inspection->team_id, $inspection, $data))], 201);
    }

    public function completeFollowUp(Request $request, string $followUp, CompleteInspectionFollowUp $complete): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $record = InspectionFollowUp::query()->where('team_id', $teamId)->findOrFail($followUp);

        return response()->json(['data' => $this->followUpResource($complete->handle($teamId, $record, (int) $request->user()->getKey()))]);
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

    private function inspectionForCurrentTeam(Request $request, string $id): Inspection
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);

        return Inspection::query()->where('team_id', $teamId)->findOrFail($id);
    }

    private function templateForCurrentTeam(Request $request, string $id): InspectionTemplate
    {
        return InspectionTemplate::query()->where('team_id', $this->teamId($request))->findOrFail($id);
    }

    /** @return array<string, mixed> */
    private function resource(Inspection $inspection): array
    {
        return ['id' => (string) $inspection->getKey(), 'type' => 'maintenance-inspection', 'attributes' => ['title' => $inspection->title, 'template_key' => $inspection->template_key, 'status' => $inspection->status, 'outcome' => $inspection->outcome, 'inspected_at' => $inspection->inspected_at?->toISOString(), 'inspector_id' => $inspection->inspector_id, 'readings' => $inspection->readings, 'failures' => $inspection->failures, 'signature' => $inspection->signature, 'certificate' => $inspection->certificate, 'follow_up' => $inspection->follow_up]];
    }

    private function templateResource(InspectionTemplate $template): array
    {
        return ['id' => (string) $template->getKey(), 'type' => 'maintenance-inspection-template', 'attributes' => ['key' => $template->key, 'name' => $template->name, 'description' => $template->description, 'checklist' => $template->checklist, 'is_active' => $template->is_active]];
    }

    private function followUpResource(InspectionFollowUp $followUp): array
    {
        return ['id' => (string) $followUp->getKey(), 'type' => 'maintenance-inspection-follow-up', 'attributes' => ['inspection_id' => $followUp->inspection_id, 'assigned_to' => $followUp->assigned_to, 'title' => $followUp->title, 'description' => $followUp->description, 'status' => $followUp->status, 'due_at' => $followUp->due_at?->toISOString(), 'completed_at' => $followUp->completed_at?->toISOString(), 'completed_by' => $followUp->completed_by]];
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return ['title' => 'required|string|max:255', 'template_key' => 'nullable|string|max:255', 'inspector_id' => 'nullable|integer|min:1', 'inspected_at' => 'nullable|date', 'readings' => 'nullable|array', 'failures' => 'nullable|array', 'signature' => 'nullable|string|max:10000', 'certificate' => 'nullable|string|max:255', 'follow_up' => 'nullable|array'];
    }
}
