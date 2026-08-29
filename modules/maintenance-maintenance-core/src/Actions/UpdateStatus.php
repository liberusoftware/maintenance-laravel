<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Core\Models\Status;

final class UpdateStatus
{
    /** @param array<string, mixed> $attributes */
    public function execute(Status $status, array $attributes): Status
    {
        $name = trim((string) ($attributes['name'] ?? $status->name));
        $code = strtoupper(trim((string) ($attributes['code'] ?? $status->code)));
        if ($name === '' || $code === '') {
            throw ValidationException::withMessages(['name' => 'A name and code are required.']);
        }
        if (Status::query()->where('team_id', $status->team_id)->where('code', $code)->whereKeyNot($status->getKey())->exists()) {
            throw ValidationException::withMessages(['code' => 'The status code is already in use.']);
        }

        return DB::transaction(function () use ($status, $attributes, $name, $code): Status {
            if (($attributes['is_default'] ?? $status->is_default) === true) {
                Status::query()->where('team_id', $status->team_id)->whereKeyNot($status->getKey())->update(['is_default' => false]);
            }
            $status->update([
                'name' => $name, 'code' => $code,
                'color' => $attributes['color'] ?? $status->color,
                'sort_order' => (int) ($attributes['sort_order'] ?? $status->sort_order),
                'is_default' => (bool) ($attributes['is_default'] ?? $status->is_default),
                'is_active' => (bool) ($attributes['is_active'] ?? $status->is_active),
            ]);

            return $status->refresh();
        });
    }
}
