<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_preventative_plans', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->unsignedBigInteger('equipment_id')->nullable()->after('description');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('equipment_id');
            $table->unsignedBigInteger('checklist_id')->nullable()->after('assigned_to');
            $table->text('instructions')->nullable()->after('checklist_id');
            $table->unsignedInteger('estimated_duration')->nullable()->after('instructions');
            $table->index(['team_id', 'assigned_to']);
            $table->index(['team_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_preventative_plans', function (Blueprint $table): void {
            $table->dropIndex(['maintenance_preventative_plans_team_id_assigned_to_index']);
            $table->dropIndex(['maintenance_preventative_plans_team_id_equipment_id_index']);
            $table->dropColumn(['description', 'equipment_id', 'assigned_to', 'checklist_id', 'instructions', 'estimated_duration']);
        });
    }
};
