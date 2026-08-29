<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Events;

use Illuminate\Contracts\Events\Dispatcher;

final readonly class OrganizationUpdated
{
    public function __construct(public int|string $organizationId, public int|string $teamId) {}

    public static function dispatch(int|string $organizationId, int|string $teamId): void
    {
        app(Dispatcher::class)->dispatch(new self($organizationId, $teamId));
    }
}
