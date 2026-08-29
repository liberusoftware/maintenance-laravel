<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class WorkOrderEvidence extends Model
{
    protected $table = 'maintenance_work_order_evidence';

    protected $fillable = ['team_id', 'work_order_id', 'added_by', 'kind', 'label', 'reference', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'work_order_id' => 'integer', 'added_by' => 'integer', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
