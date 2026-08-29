<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class MaintenancePlan extends Model
{
    protected $table = 'maintenance_preventative_plans';

    protected $fillable = ['team_id', 'name', 'code', 'frequency_unit', 'frequency_value', 'next_due_at', 'last_completed_at', 'is_active', 'rules'];

    protected $casts = ['team_id' => 'integer', 'frequency_value' => 'integer', 'next_due_at' => 'datetime', 'last_completed_at' => 'datetime', 'is_active' => 'boolean', 'rules' => 'array'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->active()->whereNotNull('next_due_at')->where('next_due_at', '<', now());
    }

    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->active()->whereBetween('next_due_at', [now(), now()->addDays($days)]);
    }

    public function scopeUpcoming(Builder $query, int $days = 30): Builder
    {
        return $query->active()->whereBetween('next_due_at', [now(), now()->addDays($days)])->orderBy('next_due_at');
    }

    public function nextDueAfterCompletion(): ?Carbon
    {
        if ($this->last_completed_at === null || $this->frequency_unit === 'meters') {
            return $this->next_due_at;
        }

        return match ($this->frequency_unit) {
            'days' => $this->last_completed_at->copy()->addDays($this->frequency_value),
            'weeks' => $this->last_completed_at->copy()->addWeeks($this->frequency_value),
            'months' => $this->last_completed_at->copy()->addMonths($this->frequency_value),
            'years' => $this->last_completed_at->copy()->addYears($this->frequency_value),
            'hours' => $this->last_completed_at->copy()->addHours($this->frequency_value),
            default => $this->next_due_at,
        };
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
