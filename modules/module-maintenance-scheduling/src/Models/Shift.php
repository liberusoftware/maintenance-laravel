<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Shift extends Model
{
    protected $table = 'maintenance_shifts';
    protected $fillable = ['team_id', 'user_id', 'name', 'weekday', 'starts_at', 'ends_at', 'timezone', 'is_active'];
    protected $casts = ['team_id' => 'integer', 'user_id' => 'integer', 'weekday' => 'integer', 'is_active' => 'boolean'];
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
}
