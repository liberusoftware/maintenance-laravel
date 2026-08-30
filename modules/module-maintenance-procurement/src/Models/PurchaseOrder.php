<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class PurchaseOrder extends Model
{
    protected $table = 'maintenance_purchase_orders';
    protected $fillable = ['team_id', 'purchase_request_id', 'order_number', 'supplier_name', 'amount', 'currency', 'status', 'ordered_at', 'received_at', 'items', 'metadata'];
    protected $casts = ['team_id' => 'integer', 'purchase_request_id' => 'integer', 'amount' => 'decimal:2', 'ordered_at' => 'datetime', 'received_at' => 'datetime', 'items' => 'array', 'metadata' => 'array'];

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function request(): BelongsTo { return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id'); }
    public function receipts(): HasMany { return $this->hasMany(PurchaseOrderReceipt::class); }
    public function scopeOpen(Builder $query): Builder { return $query->whereIn('status', ['draft', 'ordered', 'partially_received']); }
}
