<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class TimeEntry extends Model
{
    protected $table = 'maintenance_time_entries';

    protected $fillable = ['team_id', 'user_id', 'work_order_id', 'description', 'started_at', 'ended_at', 'minutes', 'rate', 'status', 'expense_amount', 'currency'];

    protected $casts = ['team_id' => 'integer', 'user_id' => 'integer', 'work_order_id' => 'integer', 'started_at' => 'datetime', 'ended_at' => 'datetime', 'minutes' => 'integer', 'rate' => 'decimal:2', 'expense_amount' => 'decimal:2'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
