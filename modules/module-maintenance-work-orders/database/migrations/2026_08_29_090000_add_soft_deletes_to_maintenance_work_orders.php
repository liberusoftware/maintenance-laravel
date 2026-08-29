<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_work_orders', function (Blueprint $table): void {
            $table->softDeletes();
            $table->index(['team_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_work_orders', function (Blueprint $table): void {
            $table->dropIndex(['maintenance_work_orders_team_id_deleted_at_index']);
            $table->dropSoftDeletes();
        });
    }
};
