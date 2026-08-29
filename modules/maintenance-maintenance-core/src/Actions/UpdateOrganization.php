<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Core\Events\OrganizationUpdated;
use Liberu\Modules\Maintenance\Core\Models\Organization;

final class UpdateOrganization
{
    /** @param array{name?: string, code?: string, description?: string|null, state?: string} $attributes */
    public function execute(Organization $organization, array $attributes): Organization
    {
        $name = array_key_exists('name', $attributes) ? trim((string) $attributes['name']) : $organization->name;
        $code = array_key_exists('code', $attributes) ? strtoupper(trim((string) $attributes['code'])) : $organization->code;
        $state = array_key_exists('state', $attributes) ? (string) $attributes['state'] : $organization->state;

        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'A name and code are required.']);
        }
        if (! in_array($state, ['active', 'inactive'], true)) {
            throw ValidationException::withMessages(['state' => 'The organization state is invalid.']);
        }
        if (Organization::query()->where('team_id', $organization->team_id)->where('code', $code)->whereKeyNot($organization->getKey())->exists()) {
            throw ValidationException::withMessages(['code' => 'The organization code is already in use.']);
        }

        return DB::transaction(function () use ($organization, $name, $code, $state, $attributes): Organization {
            $organization->update([
                'name' => $name,
                'code' => $code,
                'description' => array_key_exists('description', $attributes) ? $attributes['description'] : $organization->description,
                'state' => $state,
            ]);
            OrganizationUpdated::dispatch($organization->getKey(), $organization->team_id);

            return $organization->refresh();
        });
    }
}
