<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class Territory extends Model
{
    protected $table = 'maintenance_territories';

    protected $fillable = ['team_id', 'name', 'code', 'description', 'is_active', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'is_active' => 'boolean', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
