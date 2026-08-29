<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_assets', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->string('model')->nullable()->after('serial_number');
            $table->string('manufacturer')->nullable()->after('model');
            $table->string('location')->nullable()->after('manufacturer');
            $table->date('purchase_date')->nullable()->after('location');
            $table->date('warranty_expiry')->nullable()->after('purchase_date');
            $table->text('notes')->nullable()->after('warranty_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_assets', function (Blueprint $table): void {
            $table->dropColumn(['description', 'model', 'manufacturer', 'location', 'purchase_date', 'warranty_expiry', 'notes']);
        });
    }
};
