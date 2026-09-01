<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\WorkOrders\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\WorkOrders\Models\WorkOrder;

class SubmitGuestWorkOrder
{
    public function handle(int $teamId, array $attributes): WorkOrder
    {
        $data = Validator::make($attributes, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'location' => ['required', 'string', 'max:255'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:64'],
            'equipment' => ['nullable', 'string', 'max:500'],
        ])->validate();

        if (! config('maintenance-work-orders.public_team_id')) {
            throw ValidationException::withMessages(['team' => 'Public maintenance intake is not configured.']);
        }

        $equipment = $data['equipment'] ?? null;
        unset($data['equipment']);

        return app(CreateWorkOrder::class)->handle($teamId, array_merge($data, [
            'submitted_at' => now(),
            'metadata' => $equipment === null ? null : ['equipment' => $equipment],
        ]));
    }
}
