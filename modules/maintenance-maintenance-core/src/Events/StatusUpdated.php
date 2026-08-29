<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Events;

use Illuminate\Contracts\Events\Dispatcher;

final readonly class StatusUpdated
{
    public function __construct(public int|string $statusId, public int|string $teamId) {}

    public static function dispatch(int|string $statusId, int|string $teamId): void
    {
        app(Dispatcher::class)->dispatch(new self($statusId, $teamId));
    }
}
