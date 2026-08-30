<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class CorrectiveAction extends Model
{
    protected $table = 'maintenance_compliance_corrective_actions';

    protected $fillable = ['team_id', 'compliance_record_id', 'title', 'description', 'status', 'assigned_to', 'due_at', 'completed_at', 'completed_by'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'compliance_record_id' => 'integer', 'assigned_to' => 'integer', 'due_at' => 'datetime', 'completed_at' => 'datetime', 'completed_by' => 'integer'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function complianceRecord(): BelongsTo
    {
        return $this->belongsTo(ComplianceRecord::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }
}
