<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Documents\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class MaintenanceDocument extends Model
{
    use SoftDeletes;

    protected $table = 'maintenance_documents';

    protected $fillable = ['team_id', 'name', 'description', 'document_type', 'file_path', 'file_name', 'mime_type', 'file_size', 'version', 'status', 'compliance_standard', 'effective_date', 'expiry_date', 'review_date', 'approval_status', 'approved_by', 'approved_at', 'documentable_type', 'documentable_id', 'created_by', 'updated_by'];

    protected $casts = ['team_id' => 'integer', 'file_size' => 'integer', 'effective_date' => 'date', 'expiry_date' => 'date', 'review_date' => 'date', 'approved_at' => 'datetime', 'approved_by' => 'integer', 'created_by' => 'integer', 'updated_by' => 'integer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(DocumentTag::class, 'maintenance_document_tag', 'document_id', 'tag_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', 'active')->whereBetween('expiry_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('expiry_date', '<', now()->toDateString());
    }

    public function scopeDueForReview(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereNotNull('review_date')->where('review_date', '<=', now()->toDateString());
    }

    public function isExpired(): bool
    {
        return $this->expiry_date?->isPast() ?? false;
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isFuture() && $this->expiry_date->lte(now()->addDays($days));
    }

    public function isDueForReview(): bool
    {
        return $this->review_date?->isPast() ?? false;
    }
}
