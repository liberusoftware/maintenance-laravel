<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class Status extends Model
{
    protected $table = 'maintenance_statuses';

    protected $fillable = ['team_id', 'name', 'code', 'color', 'sort_order', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'sort_order' => 'integer', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }
}
