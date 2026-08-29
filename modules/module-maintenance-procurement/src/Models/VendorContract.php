<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class VendorContract extends Model
{
    protected $table = 'maintenance_vendor_contracts';

    protected $fillable = ['team_id', 'vendor_name', 'contract_number', 'title', 'description', 'contract_type', 'start_date', 'end_date', 'contract_value', 'currency', 'status', 'auto_renewal', 'renewal_date', 'notes', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'start_date' => 'date', 'end_date' => 'date', 'contract_value' => 'decimal:2', 'auto_renewal' => 'boolean', 'renewal_date' => 'date', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->active()->whereBetween('end_date', [now()->toDateString(), now()->addDays(max(0, $days))->toDateString()]);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('end_date', '<', now()->toDateString())->whereIn('status', ['active', 'expired']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->start_date?->isPast() && $this->end_date?->isFuture();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->isActive() && $this->end_date->lte(now()->addDays(max(0, $days)));
    }

    public function daysUntilExpiration(): int
    {
        return max(0, (int) now()->diffInDays($this->end_date, false));
    }
}
