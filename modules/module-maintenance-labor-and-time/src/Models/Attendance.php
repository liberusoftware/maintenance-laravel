<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Attendance extends Model
{
    protected $table = 'maintenance_attendance';

    protected $fillable = ['team_id', 'user_id', 'attendance_date', 'clocked_in_at', 'clocked_out_at', 'status', 'notes'];

    protected $casts = ['team_id' => 'integer', 'user_id' => 'integer', 'attendance_date' => 'date', 'clocked_in_at' => 'datetime', 'clocked_out_at' => 'datetime'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function durationMinutes(): int
    {
        return $this->clocked_in_at === null || $this->clocked_out_at === null ? 0 : (int) $this->clocked_in_at->diffInMinutes($this->clocked_out_at);
    }
}
