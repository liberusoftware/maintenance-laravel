<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Procurement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Procurement\Models\VendorPerformanceEvaluation;

final class UpdateVendorPerformanceEvaluation
{
    /** @param array<string, mixed> $attributes */
    public function handle(int $teamId, VendorPerformanceEvaluation $evaluation, array $attributes): VendorPerformanceEvaluation
    {
        abort_unless((int) $evaluation->team_id === $teamId, 404);
        foreach (['quality_rating', 'timeliness_rating', 'communication_rating', 'cost_effectiveness_rating', 'professionalism_rating'] as $rating) {
            if (array_key_exists($rating, $attributes) && ((int) $attributes[$rating] < 0 || (int) $attributes[$rating] > 5)) {
                throw ValidationException::withMessages([$rating => 'Ratings must be between zero and five.']);
            }
        }

        return DB::transaction(function () use ($evaluation, $attributes): VendorPerformanceEvaluation {
            $evaluation->fill($attributes);
            $evaluation->overall_rating = $evaluation->calculatedOverallRating();
            $evaluation->save();

            return $evaluation->refresh();
        });
    }
}
