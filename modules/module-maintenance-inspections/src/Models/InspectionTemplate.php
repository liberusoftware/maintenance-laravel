<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class InspectionTemplate extends Model
{
    protected $table = 'maintenance_inspection_templates';

    protected $fillable = ['team_id', 'key', 'name', 'description', 'checklist', 'is_active'];

    protected $casts = ['team_id' => 'integer', 'checklist' => 'array', 'is_active' => 'boolean'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
