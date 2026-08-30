<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DocumentVersion extends Model
{
    protected $table = 'maintenance_document_versions';
    protected $fillable = ['document_id', 'version', 'file_path', 'file_name', 'mime_type', 'file_size', 'change_notes', 'created_by'];
    protected $casts = ['document_id' => 'integer', 'file_size' => 'integer', 'created_by' => 'integer'];
    public function document(): BelongsTo { return $this->belongsTo(MaintenanceDocument::class, 'document_id'); }
}
