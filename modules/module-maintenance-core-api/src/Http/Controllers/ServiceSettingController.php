<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Core\Actions\SetServiceSetting;
use Liberu\Modules\Maintenance\Core\Models\ServiceSetting;

final class ServiceSettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');
        abort_unless($request->user()->can('viewAny', ServiceSetting::class), 403);

        return response()->json(['data' => ServiceSetting::query()->where('team_id', $teamId)->orderBy('key')->get()->map(fn (ServiceSetting $setting): array => ['id' => (string) $setting->id, 'type' => 'maintenance-service-setting', 'attributes' => ['key' => $setting->key, 'value' => $setting->is_encrypted ? null : $setting->value, 'is_encrypted' => $setting->is_encrypted]])->values()]);
    }

    public function store(Request $request, SetServiceSetting $set): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');
        abort_unless($request->user()->can('create', ServiceSetting::class), 403);
        $attributes = $request->validate(['key' => ['required', 'string', 'max:128'], 'value' => ['nullable', 'string'], 'is_encrypted' => ['sometimes', 'boolean']]);
        $setting = $set->execute($teamId, $attributes['key'], $attributes['value'] ?? null, (bool) ($attributes['is_encrypted'] ?? false));

        return response()->json(['data' => ['id' => (string) $setting->id, 'type' => 'maintenance-service-setting', 'attributes' => ['key' => $setting->key, 'value' => $setting->is_encrypted ? null : $setting->value, 'is_encrypted' => $setting->is_encrypted]]], 201);
    }

    private function teamId(Request $request): ?int
    {
        $team = $request->user()?->currentTeam;

        return $team?->getKey() === null ? null : (int) $team->getKey();
    }
}
