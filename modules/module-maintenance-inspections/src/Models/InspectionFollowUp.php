<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class InspectionFollowUp extends Model
{
    protected $table = 'maintenance_inspection_follow_ups';

    protected $fillable = ['team_id', 'inspection_id', 'assigned_to', 'title', 'description', 'status', 'due_at', 'completed_at', 'completed_by'];

    protected $casts = ['team_id' => 'integer', 'inspection_id' => 'integer', 'assigned_to' => 'integer', 'due_at' => 'datetime', 'completed_at' => 'datetime', 'completed_by' => 'integer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }
}
