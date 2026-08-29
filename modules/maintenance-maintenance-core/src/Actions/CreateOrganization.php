<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Core\Events\OrganizationCreated;
use Liberu\Modules\Maintenance\Core\Models\Organization;

final class CreateOrganization
{
    public function execute(int $teamId, string $name, string $code, ?string $description = null): Organization
    {
        $name = trim($name);
        $code = strtoupper(trim($code));
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'A name and code are required.']);
        }
        if (Organization::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'The organization code is already in use.']);
        }

        return DB::transaction(function () use ($teamId, $name, $code, $description): Organization {
            $organization = Organization::query()->create([
                'team_id' => $teamId,
                'name' => $name,
                'code' => $code,
                'description' => $description,
            ]);
            OrganizationCreated::dispatch($organization->getKey(), $teamId);

            return $organization->refresh();
        });
    }
}
