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
            $table->unsignedInteger('reserved_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_stock_items', function (Blueprint $table): void {
            $table->dropColumn('reserved_quantity');
        });
    }
};
