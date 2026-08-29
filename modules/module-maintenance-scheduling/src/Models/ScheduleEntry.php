<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class ScheduleEntry extends Model
{
    protected $table = 'maintenance_schedule_entries';

    protected $fillable = ['team_id', 'title', 'description', 'resource_key', 'equipment_id', 'assigned_to', 'checklist_id', 'instructions', 'estimated_duration', 'starts_at', 'ends_at', 'status', 'territory', 'metadata', 'recurrence_type', 'recurrence_value', 'next_due_at', 'last_completed_at', 'priority'];

    protected $casts = ['team_id' => 'integer', 'equipment_id' => 'integer', 'assigned_to' => 'integer', 'checklist_id' => 'integer', 'estimated_duration' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'next_due_at' => 'datetime', 'last_completed_at' => 'datetime', 'recurrence_value' => 'integer', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeUpcoming(Builder $query, int $days = 30): Builder
    {
        return $this->scopeDueSoon($query, $days);
    }

    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereIn('status', ['scheduled', 'in_progress'])
            ->where(function (Builder $query) use ($days): void {
                $query->whereBetween('starts_at', [now(), now()->addDays(max(0, $days))])
                    ->orWhereBetween('next_due_at', [now(), now()->addDays(max(0, $days))]);
            })
            ->orderByRaw('COALESCE(next_due_at, starts_at)');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where(function (Builder $query): void {
                $query->where('ends_at', '<', now())->orWhere('next_due_at', '<', now());
            })
            ->orderBy('ends_at');
    }

    public function calculateNextDueAt(Carbon $completedAt): ?Carbon
    {
        $value = max(1, (int) $this->recurrence_value);

        return match ($this->recurrence_type) {
            'daily' => $completedAt->copy()->addDays($value),
            'weekly' => $completedAt->copy()->addWeeks($value),
            'monthly' => $completedAt->copy()->addMonths($value),
            'yearly' => $completedAt->copy()->addYears($value),
            'hours' => $completedAt->copy()->addHours($value),
            default => null,
        };
    }

    public function scopeForResource(Builder $query, string $resourceKey): Builder
    {
        return $query->where('resource_key', $resourceKey);
    }

    public function scopeInTerritory(Builder $query, string $territory): Builder
    {
        return $query->where('territory', $territory);
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
