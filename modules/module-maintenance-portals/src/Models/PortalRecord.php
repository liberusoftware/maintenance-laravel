<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Portal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class PortalRecord extends Model
{
    protected $table = 'maintenance_portal_records';

    protected $fillable = ['kind', 'title', 'description', 'status', 'requested_by', 'metadata', 'team_id'];

    protected $casts = ['metadata' => 'array', 'team_id' => 'integer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
