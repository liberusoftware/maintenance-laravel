<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class WorkOrder extends Model
{
    protected $table = 'maintenance_work_orders';

    protected $fillable = ['team_id', 'number', 'title', 'description', 'location', 'equipment_id', 'customer_id', 'assigned_to', 'due_date', 'started_at', 'estimated_minutes', 'actual_minutes', 'maintenance_plan_id', 'checklist_id', 'priority', 'status', 'requested_by', 'completed_at', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'equipment_id' => 'integer', 'customer_id' => 'integer', 'assigned_to' => 'integer', 'requested_by' => 'integer', 'maintenance_plan_id' => 'integer', 'checklist_id' => 'integer', 'due_date' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'estimated_minutes' => 'integer', 'actual_minutes' => 'integer', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
