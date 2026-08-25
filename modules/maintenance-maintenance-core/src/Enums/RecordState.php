<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Enums;

enum RecordState: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
