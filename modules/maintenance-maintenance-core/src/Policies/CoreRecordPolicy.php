<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class CoreRecordPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->teamId($user) !== null;
    }

    public function view(Authenticatable $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->teamId($user) !== null;
    }

    public function update(Authenticatable $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    public function delete(Authenticatable $user, Model $record): bool
    {
        return $this->owns($user, $record);
    }

    private function owns(Authenticatable $user, Model $record): bool
    {
        return $this->teamId($user) !== null && (int) $record->getAttribute('team_id') === $this->teamId($user);
    }

    private function teamId(Authenticatable $user): ?int
    {
        $team = $user->getAttribute('currentTeam');

        return $team?->getKey() === null ? null : (int) $team->getKey();
    }
}
