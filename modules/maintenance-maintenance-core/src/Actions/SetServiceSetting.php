<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Core\Models\ServiceSetting;

final class SetServiceSetting
{
    public function execute(int $teamId, string $key, ?string $value, bool $encrypted = false): ServiceSetting
    {
        $key = trim($key);
        if ($key === '' || strlen($key) > 128) {
            throw ValidationException::withMessages(['key' => 'A valid setting key is required.']);
        }

        return DB::transaction(fn (): ServiceSetting => ServiceSetting::query()->updateOrCreate(
            ['team_id' => $teamId, 'key' => $key],
            ['value' => $value, 'is_encrypted' => $encrypted],
        )->refresh());
    }
}
