<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Core\Actions\CreateStatus;
use Liberu\Modules\Maintenance\Core\Actions\DeleteStatus;
use Liberu\Modules\Maintenance\Core\Actions\UpdateStatus;
use Liberu\Modules\Maintenance\Core\Models\Status;

final class StatusController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');
        abort_unless($request->user()->can('viewAny', Status::class), 403);

        return response()->json(['data' => Status::query()->where('team_id', $teamId)->orderBy('sort_order')->orderBy('name')->get()->map(fn (Status $status): array => $this->resource($status))->values()]);
    }

    public function store(Request $request, CreateStatus $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');
        abort_unless($request->user()->can('create', Status::class), 403);

        return response()->json(['data' => $this->resource($create->execute($teamId, $request->validate($this->rules())))], 201);
    }

    public function update(Request $request, Status $status, UpdateStatus $update): JsonResponse
    {
        $this->authorizeRecord($request, $status, 'update');

        return response()->json(['data' => $this->resource($update->execute($status, $request->validate($this->rules(true))))]);
    }

    public function destroy(Request $request, Status $status, DeleteStatus $delete): Response
    {
        $this->authorizeRecord($request, $status, 'delete');
        $delete->execute($status);

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function resource(Status $status): array
    {
        return ['id' => (string) $status->getKey(), 'type' => 'maintenance-status', 'attributes' => $status->only(['name', 'code', 'color', 'sort_order', 'is_default', 'is_active'])];
    }

    /** @return array<string, array<int, string>> */
    private function rules(bool $sometimes = false): array
    {
        $prefix = $sometimes ? 'sometimes' : 'required';

        return ['name' => [$prefix, 'string', 'max:255'], 'code' => [$prefix, 'string', 'max:32'], 'color' => ['sometimes', 'nullable', 'string', 'max:32'], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'is_default' => ['sometimes', 'boolean'], 'is_active' => ['sometimes', 'boolean']];
    }

    private function authorizeRecord(Request $request, Status $status, string $ability): void
    {
        abort_unless($this->teamId($request) === $status->team_id && $request->user()->can($ability, $status), 404);
    }

    private function teamId(Request $request): ?int
    {
        $team = $request->user()?->currentTeam;

        return $team?->getKey() === null ? null : (int) $team->getKey();
    }
}
