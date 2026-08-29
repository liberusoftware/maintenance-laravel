<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Core\Events\StatusCreated;
use Liberu\Modules\Maintenance\Core\Models\Status;

final class CreateStatus
{
    /** @param array{name: string, code: string, color?: string|null, sort_order?: int, is_default?: bool, is_active?: bool} $attributes */
    public function execute(int $teamId, array $attributes): Status
    {
        $name = trim($attributes['name']);
        $code = strtoupper(trim($attributes['code']));
        $this->validate($name, $code, $teamId);

        $status = DB::transaction(function () use ($teamId, $attributes, $name, $code): Status {
            if (($attributes['is_default'] ?? false) === true) {
                Status::query()->where('team_id', $teamId)->update(['is_default' => false]);
            }

            return Status::query()->create([
                'team_id' => $teamId, 'name' => $name, 'code' => $code,
                'color' => $attributes['color'] ?? null, 'sort_order' => (int) ($attributes['sort_order'] ?? 0),
                'is_default' => (bool) ($attributes['is_default'] ?? false), 'is_active' => (bool) ($attributes['is_active'] ?? true),
            ])->refresh();
        });
        StatusCreated::dispatch($status->getKey(), $teamId);

        return $status;
    }

    private function validate(string $name, string $code, int $teamId): void
    {
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'A name and code are required.']);
        }
        if (Status::query()->where('team_id', $teamId)->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['code' => 'The status code is already in use.']);
        }
    }
}
