<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Customer extends Model
{
    protected $table = 'maintenance_customers';

    protected $fillable = ['team_id', 'name', 'code', 'email', 'phone', 'address', 'city', 'state', 'zip', 'website', 'industry', 'description', 'type', 'contact_person', 'payment_terms', 'notes', 'is_active'];

    protected $casts = ['team_id' => 'integer', 'is_active' => 'boolean'];

    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->whereIn('type', ['supplier', 'both']);
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->whereIn('type', ['customer', 'both']);
    }

    public function scopeVendors(Builder $query): Builder
    {
        return $query->whereIn('type', ['vendor', 'supplier', 'both']);
    }

    public function isSupplier(): bool
    {
        return in_array($this->type, ['supplier', 'both', 'vendor'], true);
    }

    public function isCustomer(): bool
    {
        return in_array($this->type, ['customer', 'both'], true);
    }

    public function isVendor(): bool
    {
        return in_array($this->type, ['vendor', 'supplier', 'both'], true);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
