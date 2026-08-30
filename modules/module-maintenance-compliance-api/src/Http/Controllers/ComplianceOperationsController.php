<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceIncident;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateCompliancePermit;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRequirement;
use Liberu\Modules\Maintenance\Compliance\Actions\CreateComplianceRiskAssessment;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceIncident;
use Liberu\Modules\Maintenance\Compliance\Models\CompliancePermit;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRequirement;
use Liberu\Modules\Maintenance\Compliance\Models\ComplianceRiskAssessment;

final class ComplianceOperationsController extends Controller
{
    public function requirements(Request $request): JsonResponse
    {
        return $this->index($request, ComplianceRequirement::class, 'maintenance-compliance-requirement');
    }

    public function storeRequirement(Request $request, CreateComplianceRequirement $create): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:80'], 'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'], 'status' => ['nullable', 'string', 'max:40'], 'expires_at' => ['nullable', 'date'], 'metadata' => ['nullable', 'array']]);

        return $this->store($request, ComplianceRequirement::class, $create->handle($this->teamId($request), $data), 'maintenance-compliance-requirement');
    }

    public function permits(Request $request): JsonResponse
    {
        return $this->index($request, CompliancePermit::class, 'maintenance-compliance-permit');
    }

    public function storePermit(Request $request, CreateCompliancePermit $create): JsonResponse
    {
        $data = $request->validate(['number' => ['required', 'string', 'max:80'], 'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'], 'status' => ['nullable', 'string', 'max:40'], 'issued_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'], 'metadata' => ['nullable', 'array']]);

        return $this->store($request, CompliancePermit::class, $create->handle($this->teamId($request), $data), 'maintenance-compliance-permit');
    }

    public function riskAssessments(Request $request): JsonResponse
    {
        return $this->index($request, ComplianceRiskAssessment::class, 'maintenance-compliance-risk-assessment');
    }

    public function storeRiskAssessment(Request $request, CreateComplianceRiskAssessment $create): JsonResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'], 'status' => ['nullable', 'string', 'max:40'], 'severity' => ['nullable', 'string', 'max:40'], 'score' => ['nullable', 'integer', 'between:0,100'], 'reviewed_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date'], 'metadata' => ['nullable', 'array']]);

        return $this->store($request, ComplianceRiskAssessment::class, $create->handle($this->teamId($request), $data), 'maintenance-compliance-risk-assessment');
    }

    public function incidents(Request $request): JsonResponse
    {
        return $this->index($request, ComplianceIncident::class, 'maintenance-compliance-incident');
    }

    public function storeIncident(Request $request, CreateComplianceIncident $create): JsonResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'], 'status' => ['nullable', 'string', 'max:40'], 'severity' => ['nullable', 'string', 'max:40'], 'occurred_at' => ['nullable', 'date'], 'reviewed_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date'], 'metadata' => ['nullable', 'array']]);

        return $this->store($request, ComplianceIncident::class, $create->handle($this->teamId($request), $data), 'maintenance-compliance-incident');
    }

    private function index(Request $request, string $model, string $type): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_unless($request->user()->can('viewAny', $model), 403);
        $query = $model::query()->where('team_id', $teamId)->latest();
        if ($request->boolean('expired')) {
            $query->expired();
        }

        return response()->json(['data' => $query->get()->map(fn (object $record): array => $this->resource($record, $type))->values()]);
    }

    private function store(Request $request, string $model, object $record, string $type): JsonResponse
    {
        abort_unless($request->user()->can('create', $model), 403);

        return response()->json(['data' => $this->resource($record, $type)], 201);
    }

    private function teamId(Request $request): int
    {
        $teamId = $request->user()?->currentTeam?->getKey();
        abort_if($teamId === null, 403);

        return (int) $teamId;
    }

    private function resource(object $record, string $type): array
    {
        $attributes = $record->getAttributes();
        foreach (['expires_at', 'issued_at', 'reviewed_at', 'occurred_at', 'created_at', 'updated_at'] as $field) {
            if ($record->{$field} !== null) {
                $attributes[$field] = $record->{$field}->toISOString();
            }
        }

        return ['id' => (string) $record->getKey(), 'type' => $type, 'attributes' => $attributes];
    }
}
