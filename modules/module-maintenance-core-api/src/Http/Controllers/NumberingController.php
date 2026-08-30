<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Liberu\Modules\Maintenance\Core\Actions\ConfigureNumbering;
use Liberu\Modules\Maintenance\Core\Actions\IssueNumber;

final class NumberingController extends Controller
{
    public function configure(Request $request, ConfigureNumbering $configure): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');
        $attributes = $request->validate([
            'document_type' => ['required', 'string', 'max:64'],
            'prefix' => ['required', 'string', 'max:32'],
            'padding' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);
        $sequence = $configure->execute($teamId, $attributes['document_type'], $attributes['prefix'], (int) ($attributes['padding'] ?? 6));

        return response()->json(['data' => [
            'id' => (string) $sequence->getKey(),
            'type' => 'maintenance-numbering-sequence',
            'attributes' => $sequence->only(['document_type', 'prefix', 'next_number', 'padding']),
        ]]);
    }

    public function issue(Request $request, IssueNumber $issue): JsonResponse
    {
        $teamId = $this->teamId($request);
        abort_if($teamId === null, 403, 'A current team context is required.');
        $attributes = $request->validate(['document_type' => ['required', 'string', 'max:64']]);

        return response()->json(['data' => [
            'type' => 'maintenance-issued-number',
            'attributes' => ['document_type' => $attributes['document_type'], 'number' => $issue->execute($teamId, $attributes['document_type'])],
        ]]);
    }

    private function teamId(Request $request): ?int
    {
        $team = $request->user()?->currentTeam;

        return $team?->getKey() === null ? null : (int) $team->getKey();
    }
}
