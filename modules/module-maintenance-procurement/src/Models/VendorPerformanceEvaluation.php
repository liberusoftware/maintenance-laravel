<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class VendorPerformanceEvaluation extends Model
{
    protected $table = 'maintenance_vendor_evaluations';

    protected $fillable = ['team_id', 'vendor_contract_id', 'vendor_name', 'evaluation_date', 'evaluated_by', 'quality_rating', 'timeliness_rating', 'communication_rating', 'cost_effectiveness_rating', 'professionalism_rating', 'overall_rating', 'strengths', 'areas_for_improvement', 'comments', 'would_recommend'];

    protected $casts = ['team_id' => 'integer', 'vendor_contract_id' => 'integer', 'evaluation_date' => 'date', 'evaluated_by' => 'integer', 'quality_rating' => 'integer', 'timeliness_rating' => 'integer', 'communication_rating' => 'integer', 'cost_effectiveness_rating' => 'integer', 'professionalism_rating' => 'integer', 'overall_rating' => 'decimal:2', 'would_recommend' => 'boolean'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(VendorContract::class, 'vendor_contract_id');
    }

    public function scopeForVendor(Builder $query, string $vendorName): Builder
    {
        return $query->where('vendor_name', $vendorName);
    }

    public function scopeHighPerformance(Builder $query, float $threshold = 4.0): Builder
    {
        return $query->where('overall_rating', '>=', $threshold);
    }

    public function scopeLowPerformance(Builder $query, float $threshold = 3.0): Builder
    {
        return $query->where('overall_rating', '<', $threshold);
    }

    /** @return array<int, int> */
    public function ratingValues(): array
    {
        return [(int) $this->quality_rating, (int) $this->timeliness_rating, (int) $this->communication_rating, (int) $this->cost_effectiveness_rating, (int) $this->professionalism_rating];
    }

    public function calculatedOverallRating(): float
    {
        $ratings = array_filter($this->ratingValues(), fn (int $rating): bool => $rating > 0);

        return $ratings === [] ? 0.0 : round(array_sum($ratings) / count($ratings), 2);
    }
}
