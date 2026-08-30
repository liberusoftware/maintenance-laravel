<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Actions\CompleteCorrectiveAction;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceEvidence;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateCorrectiveAction;
use Liberu\Modules\Maintenance\Compliance\Actions\DeleteComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Actions\UpdateComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRecord;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceEvidence;
use Liberu\Modules\Maintenance\Compliance\Models\CorrectiveAction;

class ComplianceRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', ComplianceRecord::class), 403);
        $query = ComplianceRecord::where('team_id', $teamId);
        if ($request->filled('kind')) {
            $query->where('kind', $request->string('kind')->toString());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->boolean('expired')) {
            $query->expired();
        } elseif ($request->boolean('current')) {
            $query->current();
        }
        $items = $query->latest()->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (ComplianceRecord $record) => $this->resource($record))->values(), 'meta' => ['total' => $items->total()]]);
    }

    public function store(Request $request, CreateComplianceRecord $create): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', ComplianceRecord::class), 403);
        $data = $request->validate(['kind' => 'required|string|max:255', 'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000', 'status' => 'nullable|string|max:40', 'expires_at' => 'nullable|date', 'metadata' => 'nullable|array']);

        return response()->json(['data' => $this->resource($create->handle((int) $teamId, $data))], 201);
    }

    public function show(Request $request, ComplianceRecord $record): JsonResponse
    {
        abort_unless((int) $request->user()?->currentTeam?->getKey() === (int) $record->team_id && $request->user()->can('view', $record), 404);

        return response()->json(['data' => $this->resource($record)]);
    }

    public function update(Request $request, ComplianceRecord $record, UpdateComplianceRecord $update): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless((int) $teamId === (int) $record->team_id && $request->user()->can('update', $record), 404);
        $data = $request->validate(['kind' => 'sometimes|required|string|max:255', 'title' => 'sometimes|required|string|max:255', 'description' => 'sometimes|nullable|string|max:10000', 'status' => 'sometimes|string|max:40', 'expires_at' => 'sometimes|nullable|date', 'metadata' => 'sometimes|nullable|array']);

        return response()->json(['data' => $this->resource($update->handle((int) $teamId, $record, $data))]);
    }

    public function destroy(Request $request, ComplianceRecord $record, DeleteComplianceRecord $delete): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless((int) $teamId === (int) $record->team_id && $request->user()->can('delete', $record), 404);
        $delete->handle((int) $teamId, $record);

        return response()->json(null, 204);
    }

    public function evidence(Request $request, string $record): JsonResponse
    {
        $record = $this->recordForCurrentTeam($request, $record);
        $this->authorizeRecord($request, $record, 'view');

        return response()->json(['data' => $record->evidence()->latest()->get()->map(fn (ComplianceEvidence $evidence): array => $this->evidenceResource($evidence))->values()]);
    }

    public function storeEvidence(Request $request, string $record, CreateComplianceEvidence $create): JsonResponse
    {
        $record = $this->recordForCurrentTeam($request, $record);
        $teamId = $this->authorizeRecord($request, $record, 'update');
        $data = $request->validate(['kind' => ['required', 'string', 'max:80'], 'label' => ['required', 'string', 'max:255'], 'reference' => ['nullable', 'string', 'max:1000'], 'captured_at' => ['nullable', 'date'], 'metadata' => ['nullable', 'array']]);
        $data['recorded_by'] = (int) $request->user()->getKey();

        return response()->json(['data' => $this->evidenceResource($create->handle($teamId, $record, $data))], 201);
    }

    public function correctiveActions(Request $request, string $record): JsonResponse
    {
        $record = $this->recordForCurrentTeam($request, $record);
        $this->authorizeRecord($request, $record, 'view');

        return response()->json(['data' => $record->correctiveActions()->latest()->get()->map(fn (CorrectiveAction $action): array => $this->correctiveActionResource($action))->values()]);
    }

    public function storeCorrectiveAction(Request $request, string $record, CreateCorrectiveAction $create): JsonResponse
    {
        $record = $this->recordForCurrentTeam($request, $record);
        $teamId = $this->authorizeRecord($request, $record, 'update');
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'], 'assigned_to' => ['nullable', 'integer', 'min:1'], 'due_at' => ['nullable', 'date']]);

        return response()->json(['data' => $this->correctiveActionResource($create->handle($teamId, $record, $data))], 201);
    }

    public function completeCorrectiveAction(Request $request, string $record, string $action, CompleteCorrectiveAction $complete): JsonResponse
    {
        $record = $this->recordForCurrentTeam($request, $record);
        $teamId = $this->authorizeRecord($request, $record, 'update');
        $action = CorrectiveAction::query()->where('team_id', $teamId)->where('compliance_record_id', $record->getKey())->findOrFail($action);

        return response()->json(['data' => $this->correctiveActionResource($complete->handle($teamId, $action, (int) $request->user()->getKey()))]);
    }

    private function resource(ComplianceRecord $record): array
    {
        return ['id' => (string) $record->getKey(), 'type' => 'maintenance-compliance', 'attributes' => ['kind' => $record->kind, 'title' => $record->title, 'description' => $record->description, 'status' => $record->status, 'expires_at' => $record->expires_at?->toISOString(), 'metadata' => $record->metadata]];
    }

    private function authorizeRecord(Request $request, ComplianceRecord $record, string $ability): int
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless((int) $teamId === (int) $record->team_id && $request->user()->can($ability, $record), 404);

        return (int) $teamId;
    }

    private function recordForCurrentTeam(Request $request, string $key): ComplianceRecord
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return ComplianceRecord::query()->where('team_id', $teamId)->findOrFail($key);
    }

    private function evidenceResource(ComplianceEvidence $evidence): array
    {
        return ['id' => (string) $evidence->getKey(), 'type' => 'maintenance-compliance-evidence', 'attributes' => ['compliance_record_id' => $evidence->compliance_record_id, 'kind' => $evidence->kind, 'label' => $evidence->label, 'reference' => $evidence->reference, 'captured_at' => $evidence->captured_at?->toISOString(), 'recorded_by' => $evidence->recorded_by, 'metadata' => $evidence->metadata]];
    }

    private function correctiveActionResource(CorrectiveAction $action): array
    {
        return ['id' => (string) $action->getKey(), 'type' => 'maintenance-compliance-corrective-action', 'attributes' => ['compliance_record_id' => $action->compliance_record_id, 'title' => $action->title, 'description' => $action->description, 'status' => $action->status, 'assigned_to' => $action->assigned_to, 'due_at' => $action->due_at?->toISOString(), 'completed_at' => $action->completed_at?->toISOString(), 'completed_by' => $action->completed_by]];
    }
}
