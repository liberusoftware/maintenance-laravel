<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class ServiceWindow extends Model
{
    protected $table = 'maintenance_site_service_windows';

    protected $fillable = ['team_id', 'site_id', 'weekday', 'starts_at', 'ends_at', 'timezone', 'is_available'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'site_id' => 'integer', 'weekday' => 'integer', 'is_available' => 'boolean'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
