<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_schedule_entries', function (Blueprint $table): void {
            $table->string('recurrence_type')->nullable()->after('status');
            $table->unsignedInteger('recurrence_value')->default(1)->after('recurrence_type');
            $table->dateTime('next_due_at')->nullable()->after('recurrence_value');
            $table->dateTime('last_completed_at')->nullable()->after('next_due_at');
            $table->string('priority')->default('medium')->after('last_completed_at');
            $table->index(['team_id', 'next_due_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_schedule_entries', function (Blueprint $table): void {
            $table->dropIndex(['maintenance_schedule_entries_team_id_next_due_at_status_index']);
            $table->dropColumn(['recurrence_type', 'recurrence_value', 'next_due_at', 'last_completed_at', 'priority']);
        });
    }
};
