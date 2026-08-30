<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class CommercialRecord extends Model
{
    protected $table = 'maintenance_commercial_records';

    protected $fillable = ['kind', 'title', 'description', 'amount', 'currency', 'status', 'metadata', 'team_id'];

    protected $casts = ['amount' => 'decimal:2', 'metadata' => 'array', 'team_id' => 'integer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CommercialLine::class)->orderBy('sort_order');
    }

    public function coverages(): HasMany
    {
        return $this->hasMany(CommercialCoverage::class);
    }
}
