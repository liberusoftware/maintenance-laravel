<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Scheduling\Actions\CreateAvailabilityWindow;
use Liberu\Modules\Maintenance\Scheduling\Models\AvailabilityWindow;

final class AvailabilityWindowController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $query = AvailabilityWindow::query()->where('team_id', $teamId);
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return response()->json(['data' => $query->orderBy('weekday')->orderBy('starts_at')->get()->map(fn (AvailabilityWindow $window): array => $this->resource($window))->values()]);
    }

    public function store(Request $request, CreateAvailabilityWindow $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403);
        $data = $request->validate(['user_id' => ['required', 'integer'], 'weekday' => ['required', 'integer', 'between:0,6'], 'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'], 'timezone' => ['nullable', 'timezone'], 'is_available' => ['nullable', 'boolean']]);

        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    private function teamId(Request $request): ?int
    {
        return $request->user()?->currentTeam?->getKey() === null ? null : (int) $request->user()->currentTeam->getKey();
    }

    private function resource(AvailabilityWindow $window): array
    {
        return ['id' => (string) $window->getKey(), 'type' => 'maintenance-availability-window', 'attributes' => ['user_id' => $window->user_id, 'weekday' => $window->weekday, 'starts_at' => $window->starts_at, 'ends_at' => $window->ends_at, 'timezone' => $window->timezone, 'is_available' => $window->is_available]];
    }
}
