<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class Location extends Model
{
    protected $table = 'maintenance_site_locations';

    protected $fillable = ['team_id', 'site_id', 'name', 'address', 'access_details', 'hazards', 'latitude', 'longitude', 'is_active'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'site_id' => 'integer', 'latitude' => 'float', 'longitude' => 'float', 'is_active' => 'boolean'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
