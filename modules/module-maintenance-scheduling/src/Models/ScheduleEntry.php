<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Models;

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
}
