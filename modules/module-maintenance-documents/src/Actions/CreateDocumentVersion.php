<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Modules\Maintenance\Documents\Models\DocumentVersion;
use Liberu\Modules\Maintenance\Documents\Models\MaintenanceDocument;

final class CreateDocumentVersion
{
    public function handle(int $teamId, MaintenanceDocument $document, array $attributes): DocumentVersion
    {
        abort_unless((int) $document->team_id === $teamId, 404);

        return DB::transaction(function () use ($document, $attributes): DocumentVersion {
            $version = $document->versions()->create($attributes);
            $document->update(['version' => $version->version, 'file_path' => $version->file_path, 'file_name' => $version->file_name, 'mime_type' => $version->mime_type, 'file_size' => $version->file_size, 'updated_by' => $attributes['created_by'] ?? null]);

            return $version->refresh();
        });
    }
}
