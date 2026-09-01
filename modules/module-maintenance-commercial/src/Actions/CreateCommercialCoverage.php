<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Commercial\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialCoverage;
use Liberu\Modules\Maintenance\Commercial\Models\CommercialRecord;

final class CreateCommercialCoverage
{
    public function handle(int $teamId, CommercialRecord $record, array $attributes): CommercialCoverage
    {
        abort_unless((int) $record->team_id === $teamId, 404);
        if (blank($attributes['covered_asset_id'] ?? null) && blank($attributes['service_name'] ?? null)) {
            throw ValidationException::withMessages(['service_name' => 'A covered asset or service name is required.']);
        }
        if (isset($attributes['covered_asset_id']) && ! \DB::table('maintenance_assets')->where('team_id', $teamId)->whereKey($attributes['covered_asset_id'])->exists()) {
            throw ValidationException::withMessages(['covered_asset_id' => 'The covered asset must belong to the current team.']);
        }

        return CommercialCoverage::create(array_merge($attributes, ['team_id' => $teamId, 'commercial_record_id' => $record->getKey(), 'coverage_type' => $attributes['coverage_type'] ?? 'service', 'currency' => strtoupper((string) ($attributes['currency'] ?? 'USD'))]));
    }
}
