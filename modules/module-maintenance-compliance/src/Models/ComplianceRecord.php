<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class ComplianceRecord extends Model
{
    protected $table = 'maintenance_compliance_records';

    protected $fillable = ['kind', 'title', 'description', 'status', 'expires_at', 'metadata', 'team_id'];

    protected $casts = ['expires_at' => 'datetime', 'metadata' => 'array', 'team_id' => 'integer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
