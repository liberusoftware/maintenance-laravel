<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class DocumentTag extends Model
{
    protected $table = 'maintenance_document_tags';
    protected $fillable = ['team_id', 'name', 'slug', 'description', 'color'];
    protected $casts = ['team_id' => 'integer'];
    public function documents(): BelongsToMany { return $this->belongsToMany(MaintenanceDocument::class, 'maintenance_document_tag', 'tag_id', 'document_id'); }
}
