<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\VendorPerformanceEvaluation;

final class CreateVendorPerformanceEvaluation
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, array $attributes): VendorPerformanceEvaluation
    {
        $vendor = trim((string) ($attributes['vendor_name'] ?? ''));
        if ($vendor === '' || empty($attributes['evaluation_date'])) {
            throw ValidationException::withMessages(['vendor_name' => 'Vendor and evaluation date are required.']);
        }
        $ratings = ['quality_rating', 'timeliness_rating', 'communication_rating', 'cost_effectiveness_rating', 'professionalism_rating'];
        foreach ($ratings as $rating) {
            $value = (int) ($attributes[$rating] ?? 0);
            if ($value < 0 || $value > 5) {
                throw ValidationException::withMessages([$rating => 'Ratings must be between zero and five.']);
            }
        }
        $evaluation = new VendorPerformanceEvaluation(array_merge($attributes, ['team_id' => $teamId, 'vendor_name' => $vendor]));
        $evaluation->overall_rating = $evaluation->calculatedOverallRating();

        return DB::transaction(fn (): VendorPerformanceEvaluation => tap($evaluation)->save());
    }
}
