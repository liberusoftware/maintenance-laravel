<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Customer extends Model
{
    protected $table = 'maintenance_customers';

    protected $fillable = ['team_id', 'name', 'code', 'email', 'phone', 'notes', 'is_active'];

    protected $casts = ['team_id' => 'integer', 'is_active' => 'boolean'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
