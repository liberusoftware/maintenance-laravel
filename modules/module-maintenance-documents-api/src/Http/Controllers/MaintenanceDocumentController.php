<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Documents\Actions\ApproveMaintenanceDocument;
use Liberu\Modules\Maintenance\Documents\Actions\CreateDocumentVersion;
use Liberu\Modules\Maintenance\Documents\Actions\CreateMaintenanceDocument;
use Liberu\Modules\Maintenance\Documents\Models\DocumentVersion;
use Liberu\Modules\Maintenance\Documents\Models\MaintenanceDocument;

final class MaintenanceDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_unless($request->user()->can('viewAny', MaintenanceDocument::class), 403);
        $query = MaintenanceDocument::query()->where('team_id', $teamId);
        foreach (['document_type', 'status', 'approval_status'] as $filter) {
            if ($request->filled($filter)) $query->where($filter, $request->string($filter)->toString());
        }
        if ($request->boolean('expired')) $query->expired();
        if ($request->boolean('due_for_review')) $query->dueForReview();
        $documents = $query->latest()->paginate(min($request->integer('per_page', 25), 100));
        return response()->json(['data' => $documents->getCollection()->map(fn (MaintenanceDocument $document): array => $this->resource($document))->values(), 'meta' => ['total' => $documents->total()]]);
    }

    public function store(Request $request, CreateMaintenanceDocument $create): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_unless($request->user()->can('create', MaintenanceDocument::class), 403);
        $data = $request->validate($this->rules());
        $data['created_by'] = (int) $request->user()->getKey();
        return response()->json(['data' => $this->resource($create->handle($teamId, $data))], 201);
    }

    public function show(Request $request, string $document): JsonResponse
    {
        $document = $this->document($request, $document);
        abort_unless($request->user()->can('view', $document), 404);
        return response()->json(['data' => $this->resource($document->load('tags', 'versions'))]);
    }

    public function update(Request $request, string $document): JsonResponse
    {
        $document = $this->document($request, $document);
        abort_unless($request->user()->can('update', $document), 404);
        $document->update(array_merge($request->validate($this->rules(true)), ['updated_by' => (int) $request->user()->getKey()]));
        return response()->json(['data' => $this->resource($document->refresh())]);
    }

    public function destroy(Request $request, string $document): JsonResponse
    {
        $document = $this->document($request, $document);
        abort_unless($request->user()->can('delete', $document), 404);
        $document->delete();
        return response()->json(null, 204);
    }

    public function approve(Request $request, string $document, ApproveMaintenanceDocument $approve): JsonResponse
    {
        $document = $this->document($request, $document);
        abort_unless($request->user()->can('update', $document), 404);
        return response()->json(['data' => $this->resource($approve->handle($this->teamId($request), $document, (int) $request->user()->getKey()))]);
    }

    public function versions(Request $request, string $document): JsonResponse
    {
        $document = $this->document($request, $document);
        abort_unless($request->user()->can('view', $document), 404);
        return response()->json(['data' => $document->versions()->latest()->get()->map(fn (DocumentVersion $version): array => $this->versionResource($version))->values()]);
    }

    public function storeVersion(Request $request, string $document, CreateDocumentVersion $create): JsonResponse
    {
        $document = $this->document($request, $document);
        abort_unless($request->user()->can('update', $document), 404);
        $data = $request->validate(['version' => ['required', 'string', 'max:50'], 'file_path' => ['nullable', 'string', 'max:500'], 'file_name' => ['nullable', 'string', 'max:255'], 'mime_type' => ['nullable', 'string', 'max:255'], 'file_size' => ['nullable', 'integer', 'min:0'], 'change_notes' => ['nullable', 'string', 'max:10000']]);
        $data['created_by'] = (int) $request->user()->getKey();
        return response()->json(['data' => $this->versionResource($create->handle($this->teamId($request), $document, $data))], 201);
    }

    private function teamId(Request $request): int { $id = $request->user()?->currentTeam?->getKey(); abort_if($id === null, 403); return (int) $id; }
    private function document(Request $request, string $key): MaintenanceDocument { return MaintenanceDocument::query()->where('team_id', $this->teamId($request))->findOrFail($key); }
    private function rules(bool $sometimes = false): array { $prefix = $sometimes ? 'sometimes|' : ''; return ['name' => [$prefix.'required', 'string', 'max:255'], 'description' => [$prefix.'nullable', 'string', 'max:10000'], 'document_type' => [$prefix.'nullable', 'string', 'max:100'], 'file_path' => [$prefix.'nullable', 'string', 'max:500'], 'file_name' => [$prefix.'nullable', 'string', 'max:255'], 'mime_type' => [$prefix.'nullable', 'string', 'max:255'], 'file_size' => [$prefix.'nullable', 'integer', 'min:0'], 'version' => [$prefix.'nullable', 'string', 'max:50'], 'status' => [$prefix.'nullable', 'in:draft,active,archived'], 'compliance_standard' => [$prefix.'nullable', 'string', 'max:100'], 'effective_date' => [$prefix.'nullable', 'date'], 'expiry_date' => [$prefix.'nullable', 'date'], 'review_date' => [$prefix.'nullable', 'date'], 'approval_status' => [$prefix.'nullable', 'in:pending,approved,rejected']]; }
    private function resource(MaintenanceDocument $document): array { return ['id' => (string) $document->getKey(), 'type' => 'maintenance-document', 'attributes' => ['name' => $document->name, 'description' => $document->description, 'document_type' => $document->document_type, 'file_path' => $document->file_path, 'file_name' => $document->file_name, 'mime_type' => $document->mime_type, 'file_size' => $document->file_size, 'version' => $document->version, 'status' => $document->status, 'compliance_standard' => $document->compliance_standard, 'effective_date' => $document->effective_date?->toDateString(), 'expiry_date' => $document->expiry_date?->toDateString(), 'review_date' => $document->review_date?->toDateString(), 'approval_status' => $document->approval_status, 'approved_by' => $document->approved_by, 'approved_at' => $document->approved_at?->toISOString()]]; }
    private function versionResource(DocumentVersion $version): array { return ['id' => (string) $version->getKey(), 'type' => 'maintenance-document-version', 'attributes' => ['document_id' => $version->document_id, 'version' => $version->version, 'file_path' => $version->file_path, 'file_name' => $version->file_name, 'mime_type' => $version->mime_type, 'file_size' => $version->file_size, 'change_notes' => $version->change_notes, 'created_by' => $version->created_by]]; }
}
