<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class PurchaseRequest extends Model
{
    protected $table = 'maintenance_purchase_requests';

    protected $fillable = ['team_id', 'supplier_name', 'title', 'description', 'amount', 'currency', 'status', 'requested_by', 'approved_by', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'amount' => 'decimal:2', 'requested_by' => 'integer', 'approved_by' => 'integer', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->where('status', 'ordered');
    }

    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('status', 'received');
    }
}
