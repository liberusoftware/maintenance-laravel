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
            $table->text('description')->nullable()->after('title');
            $table->unsignedBigInteger('equipment_id')->nullable()->after('resource_key');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('equipment_id');
            $table->unsignedBigInteger('checklist_id')->nullable()->after('assigned_to');
            $table->text('instructions')->nullable()->after('checklist_id');
            $table->unsignedInteger('estimated_duration')->nullable()->after('instructions');
            $table->index(['team_id', 'equipment_id']);
            $table->index(['team_id', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_schedule_entries', function (Blueprint $table): void {
            $table->dropIndex(['maintenance_schedule_entries_team_id_equipment_id_index']);
            $table->dropIndex(['maintenance_schedule_entries_team_id_assigned_to_index']);
            $table->dropColumn(['description', 'equipment_id', 'assigned_to', 'checklist_id', 'instructions', 'estimated_duration']);
        });
    }
};
