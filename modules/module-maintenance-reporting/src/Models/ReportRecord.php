<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class ReportRecord extends Model
{
    protected $table = 'maintenance_reporting_records';

    protected $fillable = ['kind', 'title', 'metric_value', 'period_start', 'period_end', 'metadata', 'team_id'];

    protected $casts = ['metric_value' => 'decimal:2', 'period_start' => 'datetime', 'period_end' => 'datetime', 'metadata' => 'array', 'team_id' => 'integer'];

    public function scopeOfKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    public function scopeForPeriod(Builder $query, ?string $start = null, ?string $end = null): Builder
    {
        return $query
            ->when($start !== null, fn (Builder $builder): Builder => $builder->where(function (Builder $period) use ($start): void {
                $period->whereNull('period_end')->orWhere('period_end', '>=', $start);
            }))
            ->when($end !== null, fn (Builder $builder): Builder => $builder->where(function (Builder $period) use ($end): void {
                $period->whereNull('period_start')->orWhere('period_start', '<=', $end);
            }));
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
