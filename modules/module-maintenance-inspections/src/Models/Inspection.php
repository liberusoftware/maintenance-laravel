<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Inspection extends Model
{
    protected $table = 'maintenance_inspections';

    protected $fillable = ['team_id', 'title', 'template_key', 'status', 'outcome', 'inspected_at', 'inspector_id', 'readings', 'failures', 'signature', 'certificate', 'follow_up'];

    protected $casts = ['team_id' => 'integer', 'inspector_id' => 'integer', 'inspected_at' => 'datetime', 'readings' => 'array', 'failures' => 'array', 'follow_up' => 'array'];

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeWithOutcome(Builder $query, string $outcome): Builder
    {
        return $query->where('outcome', $outcome);
    }

    public function scopeInspectedBetween(Builder $query, ?string $start = null, ?string $end = null): Builder
    {
        return $query
            ->when($start !== null, fn (Builder $builder): Builder => $builder->where('inspected_at', '>=', $start))
            ->when($end !== null, fn (Builder $builder): Builder => $builder->where('inspected_at', '<=', $end));
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplate::class, 'template_key', 'key')->where('team_id', $this->team_id);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(InspectionFollowUp::class);
    }
}
