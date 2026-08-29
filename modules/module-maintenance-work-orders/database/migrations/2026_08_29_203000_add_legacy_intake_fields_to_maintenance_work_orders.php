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
            $table->string('guest_name')->nullable()->after('requested_by');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_phone')->nullable()->after('guest_email');
            $table->timestamp('submitted_at')->nullable()->after('guest_phone');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('submitted_at');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->unsignedBigInteger('vendor_id')->nullable()->after('customer_id');
            $table->text('notes')->nullable()->after('completed_at');
            $table->index(['team_id', 'submitted_at']);
            $table->index(['team_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_work_orders', function (Blueprint $table): void {
            $table->dropIndex(['maintenance_work_orders_team_id_submitted_at_index']);
            $table->dropIndex(['maintenance_work_orders_team_id_reviewed_at_index']);
            $table->dropColumn(['guest_name', 'guest_email', 'guest_phone', 'submitted_at', 'reviewed_by', 'reviewed_at', 'vendor_id', 'notes']);
        });
    }
};
