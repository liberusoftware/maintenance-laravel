<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Tasks\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class MaintenanceTask extends Model
{
    use SoftDeletes;

    protected $table = 'maintenance_tasks';

    protected $fillable = ['team_id', 'description', 'status', 'priority', 'due_date', 'assigned_to', 'taskable_type', 'taskable_id', 'completed_at', 'created_by', 'updated_by'];

    protected $casts = ['team_id' => 'integer', 'priority' => 'integer', 'due_date' => 'date', 'assigned_to' => 'integer', 'taskable_id' => 'integer', 'completed_at' => 'datetime', 'created_by' => 'integer', 'updated_by' => 'integer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed')->whereNotNull('due_date')->where('due_date', '<', now()->toDateString());
    }
}
