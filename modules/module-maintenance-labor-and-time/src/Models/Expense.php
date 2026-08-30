<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\LaborAndTime\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Liberu\Modules\OrganizationsTeams\Models\Team;

class Expense extends Model
{
    protected $table = 'maintenance_expenses';
    protected $fillable = ['team_id', 'user_id', 'work_order_id', 'description', 'amount', 'currency', 'status', 'metadata'];
    protected $casts = ['team_id' => 'integer', 'user_id' => 'integer', 'work_order_id' => 'integer', 'amount' => 'decimal:2', 'metadata' => 'array'];

    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
}
