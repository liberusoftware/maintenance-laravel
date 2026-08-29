<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Queries;

use Carbon\Contracts\CarbonInterface;
use Liberu\Modules\Maintenance\Report\Models\ReportRecord;

final class BuildReportSummary
{
    /** @return array{period: array{start: ?string, end: ?string}, total_records: int, published_records: int, draft_records: int, metric_total: float, by_kind: array<string, array{count: int, metric_total: float}>} */
    public function handle(int $teamId, ?CarbonInterface $start = null, ?CarbonInterface $end = null): array
    {
        $records = ReportRecord::query()
            ->where('team_id', $teamId)
            ->forPeriod($start?->toISOString(), $end?->toISOString())
            ->get(['kind', 'status', 'metric_value']);

        $byKind = [];
        foreach ($records->groupBy('kind') as $kind => $kindRecords) {
            $byKind[$kind] = [
                'count' => $kindRecords->count(),
                'metric_total' => round((float) $kindRecords->sum(fn (ReportRecord $record): float => (float) ($record->metric_value ?? 0)), 2),
            ];
        }

        return [
            'period' => ['start' => $start?->toISOString(), 'end' => $end?->toISOString()],
            'total_records' => $records->count(),
            'published_records' => $records->where('status', 'published')->count(),
            'draft_records' => $records->where('status', 'draft')->count(),
            'metric_total' => round((float) $records->sum(fn (ReportRecord $record): float => (float) ($record->metric_value ?? 0)), 2),
            'by_kind' => $byKind,
        ];
    }
}
