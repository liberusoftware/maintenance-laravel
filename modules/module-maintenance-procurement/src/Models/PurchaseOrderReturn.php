<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class PurchaseOrderReturn extends Model
{
    protected $table = 'maintenance_purchase_order_returns';

    protected $fillable = ['team_id', 'purchase_order_id', 'returned_by', 'status', 'returned_at', 'items', 'reason'];

    protected $casts = ['team_id' => 'integer', 'purchase_order_id' => 'integer', 'returned_by' => 'integer', 'returned_at' => 'datetime', 'items' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
