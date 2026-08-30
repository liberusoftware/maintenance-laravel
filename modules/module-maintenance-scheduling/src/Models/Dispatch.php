<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Dispatch extends Model
{
    protected $table = 'maintenance_dispatches';

    protected $fillable = ['team_id', 'schedule_entry_id', 'user_id', 'dispatched_by', 'status', 'dispatched_at', 'accepted_at', 'notes'];

    protected $casts = ['team_id' => 'integer', 'schedule_entry_id' => 'integer', 'user_id' => 'integer', 'dispatched_by' => 'integer', 'dispatched_at' => 'datetime', 'accepted_at' => 'datetime'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scheduleEntry(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntry::class);
    }
}
