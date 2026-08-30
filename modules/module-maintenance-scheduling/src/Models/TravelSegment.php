<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class TravelSegment extends Model
{
    protected $table = 'maintenance_travel_segments';
    protected $fillable = ['team_id', 'schedule_entry_id', 'origin', 'destination', 'planned_minutes', 'actual_minutes', 'status', 'metadata'];
    protected $casts = ['team_id' => 'integer', 'schedule_entry_id' => 'integer', 'planned_minutes' => 'integer', 'actual_minutes' => 'integer', 'metadata' => 'array'];
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function scheduleEntry(): BelongsTo { return $this->belongsTo(ScheduleEntry::class); }
}
