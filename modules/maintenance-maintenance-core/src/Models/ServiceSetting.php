<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class ServiceSetting extends Model
{
    protected $table = 'maintenance_service_settings';

    protected $fillable = ['team_id', 'key', 'value', 'is_encrypted'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'is_encrypted' => 'boolean'];
    }
}
