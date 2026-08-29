<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Reporting\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Liberu\Modules\Maintenance\Report\Actions\CreateReportRecord;
use Liberu\Modules\Maintenance\Report\Actions\DeleteReportRecord;
use Liberu\Modules\Maintenance\Report\Actions\PublishReport;
use Liberu\Modules\Maintenance\Report\Actions\UpdateReportRecord;
use Liberu\Modules\Maintenance\Report\Models\ReportKind;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;

class ReportingRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('viewAny', ReportRecord::class), 403);
        $query = ReportRecord::where('team_id', $teamId);
        $period = $request->validate(['kind' => ['sometimes', Rule::enum(ReportKind::class)], 'period_start' => 'sometimes|date', 'period_end' => 'sometimes|date|after_or_equal:period_start']);
        if (isset($period['kind'])) {
            $query->ofKind($period['kind']);
        }
        $query->forPeriod($period['period_start'] ?? null, $period['period_end'] ?? null);
        $items = $query->latest()->paginate(min($request->integer('per_page', 25), 100));

        return response()->json(['data' => $items->getCollection()->map(fn (ReportRecord $record) => $this->resource($record))->values(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
    }

    public function store(Request $request, CreateReportRecord $create): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless($request->user()->can('create', ReportRecord::class), 403);
        $data = $request->validate(['kind' => ['required', Rule::enum(ReportKind::class)], 'title' => 'required|string|max:255', 'description' => 'sometimes|nullable|string|max:10000', 'metric_value' => 'sometimes|nullable|numeric', 'period_start' => 'sometimes|nullable|date', 'period_end' => 'sometimes|nullable|date|after_or_equal:period_start', 'metadata' => 'sometimes|nullable|array']);

        return response()->json(['data' => $this->resource($create->handle((int) $teamId, $data))], 201);
    }

    public function publish(Request $request, ReportRecord $record, PublishReport $publish): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless((int) $teamId === (int) $record->team_id && $request->user()->can('update', $record), 404);

        return response()->json(['data' => $this->resource($publish->execute((int) $teamId, $record))]);
    }

    public function show(Request $request, ReportRecord $record): JsonResponse
    {
        abort_unless((int) $request->user()?->currentTeam?->getKey() === (int) $record->team_id && $request->user()->can('view', $record), 404);

        return response()->json(['data' => $this->resource($record)]);
    }

    public function update(Request $request, ReportRecord $record, UpdateReportRecord $update): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless((int) $teamId === (int) $record->team_id && $request->user()->can('update', $record), 404);
        $data = $request->validate(['kind' => ['sometimes', 'required', Rule::enum(ReportKind::class)], 'title' => 'sometimes|required|string|max:255', 'description' => 'sometimes|nullable|string|max:10000', 'metric_value' => 'sometimes|nullable|numeric', 'period_start' => 'sometimes|nullable|date', 'period_end' => 'sometimes|nullable|date|after_or_equal:period_start', 'metadata' => 'sometimes|nullable|array']);

        return response()->json(['data' => $this->resource($update->handle((int) $teamId, $record, $data))]);
    }

    public function destroy(Request $request, ReportRecord $record, DeleteReportRecord $delete): JsonResponse
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);
        abort_unless((int) $teamId === (int) $record->team_id && $request->user()->can('delete', $record), 404);
        $delete->handle((int) $teamId, $record);

        return response()->json(null, 204);
    }

    private function resource(ReportRecord $record): array
    {
        return ['id' => (string) $record->getKey(), 'type' => 'maintenance-report', 'attributes' => ['kind' => $record->kind, 'title' => $record->title, 'description' => $record->description, 'metric_value' => $record->metric_value, 'period_start' => $record->period_start?->toISOString(), 'period_end' => $record->period_end?->toISOString(), 'status' => $record->status, 'metadata' => $record->metadata, 'created_at' => $record->created_at?->toISOString(), 'updated_at' => $record->updated_at?->toISOString()]];
    }
}
