<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Api\Http\Controllers;

use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateEngineerSkill;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateShift;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateTravelSegment;
use Liberu\Modules\Maintenance\Scheduling\Actions\DispatchScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Models\Dispatch;
use Liberu\Modules\Maintenance\Scheduling\Models\EngineerSkill;
use Liberu\Modules\Maintenance\Scheduling\Models\ScheduleEntry;
use Liberu\Modules\Maintenance\Scheduling\Models\Shift;
use Liberu\Modules\Maintenance\Scheduling\Models\TravelSegment;

final class SchedulingOperationsController extends Controller
{
    public function skills(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $query = EngineerSkill::query()->where('team_id', $teamId)->orderBy('name');
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return response()->json(['data' => $query->get()->map(fn (EngineerSkill $skill): array => $this->resource($skill, 'maintenance-engineer-skill', ['team_id', 'user_id', 'name', 'proficiency', 'expires_on', 'metadata']))->values()]);
    }

    public function storeSkill(Request $request, CreateEngineerSkill $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $data = $request->validate(['user_id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:255'], 'proficiency' => ['nullable', 'integer', 'between:1,5'], 'expires_on' => ['nullable', 'date'], 'metadata' => ['nullable', 'array']]);

        return response()->json(['data' => $this->resource($create->handle($teamId, $data), 'maintenance-engineer-skill', ['team_id', 'user_id', 'name', 'proficiency', 'expires_on', 'metadata'])], 201);
    }

    public function shifts(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $query = Shift::query()->where('team_id', $teamId)->orderBy('weekday')->orderBy('starts_at');
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return response()->json(['data' => $query->get()->map(fn (Shift $shift): array => $this->resource($shift, 'maintenance-shift', ['team_id', 'user_id', 'name', 'weekday', 'starts_at', 'ends_at', 'timezone', 'is_active']))->values()]);
    }

    public function storeShift(Request $request, CreateShift $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $data = $request->validate(['user_id' => ['required', 'integer', 'min:1'], 'name' => ['nullable', 'string', 'max:255'], 'weekday' => ['required', 'integer', 'between:0,6'], 'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'], 'timezone' => ['nullable', 'timezone'], 'is_active' => ['nullable', 'boolean']]);

        return response()->json(['data' => $this->resource($create->handle($teamId, $data), 'maintenance-shift', ['team_id', 'user_id', 'name', 'weekday', 'starts_at', 'ends_at', 'timezone', 'is_active'])], 201);
    }

    public function travel(Request $request, string $scheduleEntry, CreateTravelSegment $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $entry = ScheduleEntry::query()->where('team_id', $teamId)->findOrFail($scheduleEntry);
        $data = $request->validate(['origin' => ['required', 'string', 'max:255'], 'destination' => ['required', 'string', 'max:255'], 'planned_minutes' => ['nullable', 'integer', 'min:0'], 'actual_minutes' => ['nullable', 'integer', 'min:0'], 'status' => ['nullable', 'in:planned,in_progress,completed,cancelled'], 'metadata' => ['nullable', 'array']]);

        return response()->json(['data' => $this->resource($create->handle($teamId, $entry, $data), 'maintenance-travel-segment', ['team_id', 'schedule_entry_id', 'origin', 'destination', 'planned_minutes', 'actual_minutes', 'status', 'metadata'])], 201);
    }

    public function travelIndex(Request $request, string $scheduleEntry): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $entry = ScheduleEntry::query()->where('team_id', $teamId)->findOrFail($scheduleEntry);

        return response()->json(['data' => $entry->travelSegments()->orderBy('id')->get()->map(fn (TravelSegment $segment): array => $this->resource($segment, 'maintenance-travel-segment', ['team_id', 'schedule_entry_id', 'origin', 'destination', 'planned_minutes', 'actual_minutes', 'status', 'metadata']))->values()]);
    }

    public function dispatch(Request $request, string $scheduleEntry, DispatchScheduleEntry $dispatch): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $entry = ScheduleEntry::query()->where('team_id', $teamId)->findOrFail($scheduleEntry);
        $data = $request->validate(['user_id' => ['required', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:10000']]);

        return response()->json(['data' => $this->resource($dispatch->handle($teamId, $entry, $data['user_id'], $request->user()->getKey(), $data['notes'] ?? null), 'maintenance-dispatch', ['team_id', 'schedule_entry_id', 'user_id', 'dispatched_by', 'status', 'dispatched_at', 'accepted_at', 'notes'])], 201);
    }

    public function dispatchIndex(Request $request, string $scheduleEntry): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $entry = ScheduleEntry::query()->where('team_id', $teamId)->findOrFail($scheduleEntry);

        return response()->json(['data' => $entry->dispatches()->orderBy('id')->get()->map(fn (Dispatch $dispatch): array => $this->resource($dispatch, 'maintenance-dispatch', ['team_id', 'schedule_entry_id', 'user_id', 'dispatched_by', 'status', 'dispatched_at', 'accepted_at', 'notes']))->values()]);
    }

    private function teamId(Request $request): ?int
    {
        return $request->user()?->currentTeam?->getKey() === null ? null : (int) $request->user()->currentTeam->getKey();
    }

    private function resource(object $model, string $type, array $fields): array
    {
        $attributes = [];
        foreach ($fields as $field) {
            $value = $model->{$field};
            $attributes[$field] = $value instanceof CarbonInterface ? $value->toISOString() : $value;
        }

        return ['id' => (string) $model->getKey(), 'type' => $type, 'attributes' => $attributes];
    }
}
