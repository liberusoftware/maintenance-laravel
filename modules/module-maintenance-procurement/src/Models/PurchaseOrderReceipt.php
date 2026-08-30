<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class PurchaseOrderReceipt extends Model
{
    protected $table = 'maintenance_purchase_order_receipts';

    protected $fillable = ['team_id', 'purchase_order_id', 'received_by', 'received_at', 'items', 'notes'];

    protected $casts = ['team_id' => 'integer', 'purchase_order_id' => 'integer', 'received_by' => 'integer', 'received_at' => 'datetime', 'items' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
