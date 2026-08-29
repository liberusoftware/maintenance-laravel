<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Events;

use Illuminate\Contracts\Events\Dispatcher;

final readonly class PriorityDeleted
{
    public function __construct(public int|string $priorityId, public int|string $teamId) {}

    public static function dispatch(int|string $priorityId, int|string $teamId): void
    {
        app(Dispatcher::class)->dispatch(new self($priorityId, $teamId));
    }
}
