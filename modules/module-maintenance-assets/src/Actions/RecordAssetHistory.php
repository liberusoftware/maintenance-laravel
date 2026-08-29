<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Assets\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Liberu\Modules\Maintenance\Assets\Models\Asset;

final class RecordAssetHistory
{
    public function handle(int $teamId, Asset $asset, string $type, string $note, ?int $actorId = null): Asset
    {
        abort_unless((int) $asset->team_id === $teamId, 404);
        $type = trim($type);
        $note = trim($note);
        if ($type === '' || $note === '') {
            throw ValidationException::withMessages(['history' => 'A history type and note are required.']);
        }

        return DB::transaction(function () use ($asset, $type, $note, $actorId): Asset {
            $metadata = is_array($asset->metadata) ? $asset->metadata : [];
            $history = is_array($metadata['history'] ?? null) ? $metadata['history'] : [];
            $history[] = ['type' => $type, 'note' => $note, 'actor_id' => $actorId, 'at' => now()->toISOString()];
            $metadata['history'] = $history;
            $asset->forceFill(['metadata' => $metadata])->save();

            return $asset->refresh();
        });
    }
}
