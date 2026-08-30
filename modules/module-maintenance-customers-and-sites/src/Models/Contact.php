<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class Contact extends Model
{
    protected $table = 'maintenance_contacts';

    protected $fillable = ['team_id', 'customer_id', 'name', 'email', 'phone', 'role', 'is_primary', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'customer_id' => 'integer', 'is_primary' => 'boolean', 'is_active' => 'boolean'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
