<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inspections\Actions;

use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Inspections\Models\Inspection;

final class IssueInspectionCertificate
{
    public function handle(int $teamId, Inspection $inspection, string $certificate): Inspection
    {
        abort_unless((int) $inspection->team_id === $teamId, 404);
        if ($inspection->status !== 'completed' || ! in_array($inspection->outcome, ['pass', 'conditional'], true)) {
            throw ValidationException::withMessages(['status' => 'Certificates require a completed passing or conditionally passing inspection.']);
        }
        $certificate = trim($certificate);
        if ($certificate === '') {
            throw ValidationException::withMessages(['certificate' => 'A certificate identifier is required.']);
        }
        $inspection->forceFill(['certificate' => $certificate])->save();

        return $inspection->refresh();
    }
}
