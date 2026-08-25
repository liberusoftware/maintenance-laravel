<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class WorkOrder extends Model
{
    protected $table = 'maintenance_work_orders';

    protected $fillable = ['team_id', 'number', 'title', 'description', 'priority', 'status', 'requested_by', 'completed_at', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'requested_by' => 'integer', 'completed_at' => 'datetime', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
