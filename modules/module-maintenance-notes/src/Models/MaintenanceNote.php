<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Notes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class MaintenanceNote extends Model
{
    use SoftDeletes;

    protected $table = 'maintenance_notes';

    protected $fillable = ['team_id', 'content', 'noteable_type', 'noteable_id', 'created_by', 'updated_by'];

    protected $casts = ['team_id' => 'integer', 'noteable_id' => 'integer', 'created_by' => 'integer', 'updated_by' => 'integer'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }
}
