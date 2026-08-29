<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_stock_items', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->string('category')->nullable()->after('description');
            $table->decimal('unit_cost', 14, 2)->nullable()->after('unit');
            $table->unsignedInteger('reorder_quantity')->default(0)->after('reorder_level');
            $table->string('supplier_name')->nullable()->after('location');
            $table->unsignedInteger('lead_time_days')->nullable()->after('supplier_name');
            $table->text('notes')->nullable()->after('lead_time_days');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_stock_items', function (Blueprint $table): void {
            $table->dropColumn(['description', 'category', 'unit_cost', 'reorder_quantity', 'supplier_name', 'lead_time_days', 'notes']);
        });
    }
};
