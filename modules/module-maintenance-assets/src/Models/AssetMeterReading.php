<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class AssetMeterReading extends Model
{
    protected $table = 'maintenance_asset_meter_readings';

    protected $fillable = ['team_id', 'asset_id', 'meter_id', 'value', 'recorded_at', 'recorded_by', 'notes'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'asset_id' => 'integer', 'meter_id' => 'integer', 'value' => 'float', 'recorded_at' => 'datetime', 'recorded_by' => 'integer'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(AssetMeter::class, 'meter_id');
    }
}
