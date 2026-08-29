<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Inspection extends Model
{
    protected $table = 'maintenance_inspections';

    protected $fillable = ['team_id', 'title', 'template_key', 'status', 'outcome', 'inspected_at', 'inspector_id', 'readings', 'failures', 'signature', 'certificate', 'follow_up'];

    protected $casts = ['team_id' => 'integer', 'inspector_id' => 'integer', 'inspected_at' => 'datetime', 'readings' => 'array', 'failures' => 'array', 'follow_up' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
