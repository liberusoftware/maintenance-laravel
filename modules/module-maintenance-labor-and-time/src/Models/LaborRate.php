<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class LaborRate extends Model
{
    protected $table = 'maintenance_labor_rates';

    protected $fillable = ['team_id', 'user_id', 'name', 'hourly_rate', 'currency', 'effective_from', 'effective_until'];

    protected $casts = ['team_id' => 'integer', 'user_id' => 'integer', 'hourly_rate' => 'decimal:2', 'effective_from' => 'date', 'effective_until' => 'date'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function isActive(?\DateTimeInterface $on = null): bool
    {
        $date = $on === null ? now() : $on;

        return $this->effective_from <= $date && ($this->effective_until === null || $this->effective_until >= $date);
    }
}
