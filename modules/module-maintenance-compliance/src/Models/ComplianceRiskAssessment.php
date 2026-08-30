<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class ComplianceRiskAssessment extends Model
{
    protected $table = 'maintenance_compliance_risk_assessments';

    protected $fillable = ['team_id', 'title', 'description', 'status', 'severity', 'score', 'reviewed_at', 'expires_at', 'metadata'];

    protected $casts = ['team_id' => 'integer', 'score' => 'integer', 'reviewed_at' => 'datetime', 'expires_at' => 'datetime', 'metadata' => 'array'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<', now());
    }
}
