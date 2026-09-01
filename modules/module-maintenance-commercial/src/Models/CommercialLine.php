<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class CommercialLine extends Model
{
    protected $table = 'maintenance_commercial_lines';

    protected $fillable = ['team_id', 'commercial_record_id', 'description', 'quantity', 'unit_price', 'line_total', 'sort_order'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'commercial_record_id' => 'integer', 'quantity' => 'float', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2', 'sort_order' => 'integer'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function commercialRecord(): BelongsTo
    {
        return $this->belongsTo(CommercialRecord::class);
    }
}
