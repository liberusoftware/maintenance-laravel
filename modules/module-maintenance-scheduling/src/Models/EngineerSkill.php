<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class EngineerSkill extends Model
{
    protected $table = 'maintenance_scheduling_engineer_skills';

    protected $fillable = ['team_id', 'user_id', 'name', 'proficiency', 'expires_on', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'user_id' => 'integer', 'proficiency' => 'integer', 'expires_on' => 'date', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', now()->toDateString()));
    }
}
