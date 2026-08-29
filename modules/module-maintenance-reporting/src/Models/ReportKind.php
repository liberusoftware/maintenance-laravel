<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Report\Models;

enum ReportKind: string
{
    case Backlog = 'backlog';
    case Response = 'response';
    case FirstTimeFix = 'first_time_fix';
    case Downtime = 'downtime';
    case Cost = 'cost';
    case Utilization = 'utilization';
    case Stock = 'stock';
    case Sla = 'sla';
    case Compliance = 'compliance';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $kind): array => [$kind->value => str($kind->value)->replace('_', ' ')->title()->toString()])->all();
    }
}
