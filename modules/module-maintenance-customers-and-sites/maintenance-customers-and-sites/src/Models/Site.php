<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Site extends Model
{
    protected $table = 'maintenance_sites';

    protected $fillable = ['team_id', 'customer_id', 'name', 'code', 'address', 'access_details', 'hazards', 'is_active'];

    protected $casts = ['team_id' => 'integer', 'customer_id' => 'integer', 'is_active' => 'boolean'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
