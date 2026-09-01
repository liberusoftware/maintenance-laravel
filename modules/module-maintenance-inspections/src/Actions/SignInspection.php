<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

final class SignInspection
{
    public function handle(int $teamId, Inspection $inspection, string $signature): Inspection
    {
        abort_unless((int) $inspection->team_id === $teamId, 404);
        if ($inspection->status !== 'completed') {
            throw ValidationException::withMessages(['status' => 'Only completed inspections can be signed.']);
        }
        $signature = trim($signature);
        if ($signature === '') {
            throw ValidationException::withMessages(['signature' => 'A signature is required.']);
        }
        $inspection->forceFill(['signature' => $signature])->save();

        return $inspection->refresh();
    }
}
