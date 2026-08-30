<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class EngineerSkill extends Model
{
    protected $table = 'maintenance_engineer_skills';
    protected $fillable = ['team_id', 'user_id', 'skill', 'level', 'certified_until', 'metadata'];
    protected $casts = ['team_id' => 'integer', 'user_id' => 'integer', 'level' => 'integer', 'certified_until' => 'date', 'metadata' => 'array'];

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function isCertified(): bool { return $this->certified_until === null || $this->certified_until->isFuture(); }
}
