<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class ScheduleEntry extends Model
{
    protected $table = 'maintenance_schedule_entries';

    protected $fillable = ['team_id', 'title', 'resource_key', 'starts_at', 'ends_at', 'status', 'territory', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeUpcoming(Builder $query, int $days = 30): Builder
    {
        return $query->whereIn('status', ['scheduled', 'in_progress'])
            ->whereBetween('starts_at', [now(), now()->addDays(max(0, $days))])
            ->orderBy('starts_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', ['scheduled', 'in_progress'])
            ->where('ends_at', '<', now())
            ->orderBy('ends_at');
    }
}
