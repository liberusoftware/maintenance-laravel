<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WorkOrderComment extends Model
{
    protected $table = 'maintenance_work_order_comments';

    protected $fillable = ['team_id', 'work_order_id', 'user_id', 'comment', 'is_internal'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'work_order_id' => 'integer', 'user_id' => 'integer', 'is_internal' => 'boolean'];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
