<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class CommercialCoverage extends Model
{
    protected $table = 'maintenance_commercial_coverages';

    protected $fillable = ['team_id', 'commercial_record_id', 'covered_asset_id', 'service_name', 'coverage_type', 'rate', 'currency', 'sla_hours', 'starts_on', 'ends_on', 'renewal_on', 'metadata'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'commercial_record_id' => 'integer', 'covered_asset_id' => 'integer', 'rate' => 'decimal:2', 'sla_hours' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date', 'renewal_on' => 'date', 'metadata' => 'array'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function commercialRecord(): BelongsTo
    {
        return $this->belongsTo(CommercialRecord::class);
    }

    public function scopeRenewingBefore(Builder $query, string $date): Builder
    {
        return $query->whereNotNull('renewal_on')->whereDate('renewal_on', '<=', $date);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn (Builder $query): Builder => $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', now()->toDateString()))->where(fn (Builder $query): Builder => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()->toDateString()));
    }
}
