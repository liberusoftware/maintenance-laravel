<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Actions;

use Liberu\Modules\Maintenance\Core\Models\ServiceSetting;

final class DeleteServiceSetting
{
    public function execute(ServiceSetting $setting): void
    {
        $setting->delete();
    }
}
