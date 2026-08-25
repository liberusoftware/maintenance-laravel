<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Foundation\Organizations\Models\Team;

final class Organization extends Model
{
    protected $table = 'maintenance_organizations';

    protected $fillable = ['team_id', 'name', 'code', 'description', 'state'];

    protected function casts(): array
    {
        return ['team_id' => 'integer'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
