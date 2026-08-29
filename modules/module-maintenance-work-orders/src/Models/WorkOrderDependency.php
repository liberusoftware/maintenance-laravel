<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class WorkOrderDependency extends Model
{
    protected $table = 'maintenance_work_order_dependencies';

    protected $fillable = ['team_id', 'work_order_id', 'depends_on_work_order_id'];

    protected $casts = ['team_id' => 'integer', 'work_order_id' => 'integer', 'depends_on_work_order_id' => 'integer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'depends_on_work_order_id');
    }
}
