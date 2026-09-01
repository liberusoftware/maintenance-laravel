<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_inventory_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->string('type', 32)->default('warehouse');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['team_id', 'code']);
        });

        Schema::create('maintenance_stock_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('stock_item_id')->constrained('maintenance_stock_items')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('maintenance_inventory_locations')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->timestamps();
            $table->unique(['stock_item_id', 'location_id']);
            $table->index(['team_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_stock_levels');
        Schema::dropIfExists('maintenance_inventory_locations');
    }
};
