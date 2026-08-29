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
            $table->string('location')->nullable()->after('description');
            $table->unsignedBigInteger('equipment_id')->nullable()->after('location');
            $table->unsignedBigInteger('customer_id')->nullable()->after('equipment_id');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('customer_id');
            $table->timestamp('due_date')->nullable()->after('assigned_to');
            $table->timestamp('started_at')->nullable()->after('due_date');
            $table->unsignedInteger('estimated_minutes')->nullable()->after('started_at');
            $table->unsignedInteger('actual_minutes')->nullable()->after('estimated_minutes');
            $table->unsignedBigInteger('maintenance_plan_id')->nullable()->after('actual_minutes');
            $table->unsignedBigInteger('checklist_id')->nullable()->after('maintenance_plan_id');
            $table->index(['team_id', 'due_date']);
            $table->index(['team_id', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_work_orders', function (Blueprint $table): void {
            $table->dropIndex(['maintenance_work_orders_team_id_due_date_index']);
            $table->dropIndex(['maintenance_work_orders_team_id_assigned_to_index']);
            $table->dropColumn(['location', 'equipment_id', 'customer_id', 'assigned_to', 'due_date', 'started_at', 'estimated_minutes', 'actual_minutes', 'maintenance_plan_id', 'checklist_id']);
        });
    }
};
