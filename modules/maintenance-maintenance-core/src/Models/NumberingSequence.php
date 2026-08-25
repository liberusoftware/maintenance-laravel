<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class NumberingSequence extends Model
{
    protected $table = 'maintenance_numbering_sequences';

    protected $fillable = ['team_id', 'document_type', 'prefix', 'next_number', 'padding'];

    protected function casts(): array
    {
        return ['team_id' => 'integer', 'next_number' => 'integer', 'padding' => 'integer'];
    }
}
