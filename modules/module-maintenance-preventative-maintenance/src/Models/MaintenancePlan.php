<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\PreventativeMaintenance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class MaintenancePlan extends Model
{
    protected $table = 'maintenance_preventative_plans';

    protected $fillable = ['team_id', 'name', 'code', 'frequency_unit', 'frequency_value', 'next_due_at', 'is_active', 'rules'];

    protected $casts = ['team_id' => 'integer', 'frequency_value' => 'integer', 'next_due_at' => 'datetime', 'is_active' => 'boolean', 'rules' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
