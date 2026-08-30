<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Core\Actions\CreatePriority;
use Liberu\Modules\Maintenance\Core\Actions\DeletePriority;
use Liberu\Modules\Maintenance\Core\Actions\UpdatePriority;
use Liberu\Modules\Maintenance\Core\Models\Priority;

final class PriorityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');
        abort_unless($request->user()->can('viewAny', Priority::class), 403);

        $query = Priority::query()->where('team_id', $teamId);
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json(['data' => $query->orderBy('sort_order')->orderBy('name')->get()->map(fn (Priority $priority): array => $this->resource($priority))->values()]);
    }

    public function store(Request $request, CreatePriority $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');
        abort_unless($request->user()->can('create', Priority::class), 403);

        return response()->json(['data' => $this->resource($create->execute($teamId, $request->validate($this->rules())))], 201);
    }

    public function update(Request $request, Priority $priority, UpdatePriority $update): JsonResponse
    {
        $this->authorizeRecord($request, $priority, 'update');

        return response()->json(['data' => $this->resource($update->execute($priority, $request->validate($this->rules(true))))]);
    }

    public function destroy(Request $request, Priority $priority, DeletePriority $delete): Response
    {
        $this->authorizeRecord($request, $priority, 'delete');
        $delete->execute($priority);

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function resource(Priority $priority): array
    {
        return ['id' => (string) $priority->getKey(), 'type' => 'maintenance-priority', 'attributes' => $priority->only(['name', 'code', 'color', 'sort_order', 'is_default', 'is_active'])];
    }

    /** @return array<string, array<int, string>> */
    private function rules(bool $sometimes = false): array
    {
        $prefix = $sometimes ? 'sometimes' : 'required';

        return ['name' => [$prefix, 'string', 'max:255'], 'code' => [$prefix, 'string', 'max:32'], 'color' => ['sometimes', 'nullable', 'string', 'max:32'], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'is_default' => ['sometimes', 'boolean'], 'is_active' => ['sometimes', 'boolean']];
    }

    private function authorizeRecord(Request $request, Priority $priority, string $ability): void
    {
        abort_unless($this->teamId($request) === $priority->team_id && $request->user()->can($ability, $priority), 404);
    }

    private function teamId(Request $request): ?int
    {
        $team = $request->user()?->currentTeam;

        return $team?->getKey() === null ? null : (int) $team->getKey();
    }
}
