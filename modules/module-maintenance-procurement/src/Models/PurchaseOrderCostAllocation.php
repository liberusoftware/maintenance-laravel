<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class PurchaseOrderCostAllocation extends Model
{
    protected $table = 'maintenance_purchase_order_cost_allocations';

    protected $fillable = ['team_id', 'purchase_order_id', 'cost_center', 'amount', 'currency', 'description'];

    protected $casts = ['team_id' => 'integer', 'purchase_order_id' => 'integer', 'amount' => 'decimal:2'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
