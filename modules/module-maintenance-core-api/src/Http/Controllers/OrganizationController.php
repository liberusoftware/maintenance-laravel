<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Core\Actions\CreateOrganization;
use Liberu\Modules\Maintenance\Core\Models\Organization;

final class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');

        $organizations = Organization::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return response()->json([
            'data' => $organizations->getCollection()->map(fn (Organization $organization): array => $this->resource($organization))->values(),
            'links' => [
                'first' => $organizations->url(1),
                'last' => $organizations->url($organizations->lastPage()),
                'prev' => $organizations->previousPageUrl(),
                'next' => $organizations->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $organizations->currentPage(),
                'last_page' => $organizations->lastPage(),
                'per_page' => $organizations->perPage(),
                'total' => $organizations->total(),
            ],
        ]);
    }

    public function store(Request $request, CreateOrganization $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:10000'],
        ]);
        $organization = $create->execute($teamId, $attributes['name'], $attributes['code'], $attributes['description'] ?? null);

        return response()->json(['data' => $this->resource($organization)], 201);
    }

    public function show(Request $request, Organization $organization): JsonResponse
    {
        abort_unless($this->teamId($request) === $organization->team_id, 404);

        return response()->json(['data' => $this->resource($organization)]);
    }

    private function teamId(Request $request): ?int
    {
        $team = $request->user()?->currentTeam;

        return $team?->getKey() === null ? null : (int) $team->getKey();
    }

    /** @return array<string, mixed> */
    private function resource(Organization $organization): array
    {
        return [
            'id' => (string) $organization->getKey(),
            'type' => 'maintenance-organization',
            'attributes' => [
                'name' => $organization->name,
                'code' => $organization->code,
                'description' => $organization->description,
                'state' => $organization->state,
                'created_at' => $organization->created_at?->toISOString(),
                'updated_at' => $organization->updated_at?->toISOString(),
            ],
        ];
    }
}
