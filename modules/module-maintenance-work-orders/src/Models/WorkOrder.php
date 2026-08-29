<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class WorkOrder extends Model
{
    use SoftDeletes;

    protected $table = 'maintenance_work_orders';

    protected $fillable = ['team_id', 'number', 'title', 'description', 'location', 'equipment_id', 'customer_id', 'vendor_id', 'assigned_to', 'due_date', 'started_at', 'estimated_minutes', 'actual_minutes', 'maintenance_plan_id', 'checklist_id', 'priority', 'status', 'requested_by', 'guest_name', 'guest_email', 'guest_phone', 'submitted_at', 'reviewed_by', 'reviewed_at', 'completed_at', 'notes', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'equipment_id' => 'integer', 'customer_id' => 'integer', 'vendor_id' => 'integer', 'assigned_to' => 'integer', 'requested_by' => 'integer', 'reviewed_by' => 'integer', 'maintenance_plan_id' => 'integer', 'checklist_id' => 'integer', 'due_date' => 'datetime', 'started_at' => 'datetime', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'completed_at' => 'datetime', 'estimated_minutes' => 'integer', 'actual_minutes' => 'integer', 'metadata' => 'array'];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'requested');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeTriaged(Builder $query): Builder
    {
        return $query->where('status', 'triaged');
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('status', 'blocked');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])->whereNotNull('due_date')->where('due_date', '<', now());
    }

    public function scopeAssignedToUser(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeDueWithin(Builder $query, int $days = 7): Builder
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WorkOrderComment::class);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(WorkOrderDependency::class);
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(WorkOrderDependency::class, 'depends_on_work_order_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(WorkOrderEvidence::class);
    }
}
