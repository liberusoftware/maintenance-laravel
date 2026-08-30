<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Compliance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

final class ComplianceEvidence extends Model
{
    protected $table = 'maintenance_compliance_evidence';

    protected $fillable = ['team_id', 'compliance_record_id', 'kind', 'label', 'reference', 'captured_at', 'recorded_by', 'metadata'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'compliance_record_id' => 'integer', 'captured_at' => 'datetime', 'recorded_by' => 'integer', 'metadata' => 'array'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function complianceRecord(): BelongsTo
    {
        return $this->belongsTo(ComplianceRecord::class);
    }
}
