<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class Priority extends Model
{
    protected $table = 'maintenance_priorities';

    protected $fillable = ['team_id', 'name', 'code', 'color', 'sort_order', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'sort_order' => 'integer', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }
}
