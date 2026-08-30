<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class WorkOrderTask extends Model
{
    protected $table = 'maintenance_work_order_tasks';

    protected $fillable = ['team_id', 'work_order_id', 'title', 'description', 'assigned_to', 'status', 'priority', 'due_at', 'completed_at', 'sort_order', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'work_order_id' => 'integer', 'assigned_to' => 'integer', 'due_at' => 'datetime', 'completed_at' => 'datetime', 'sort_order' => 'integer', 'metadata' => 'array'];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }
}
