<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_stock_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('part_number', 96);
            $table->string('name');
            $table->string('location')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->string('unit')->default('each');
            $table->timestamps();
            $table->unique(['team_id', 'part_number']);
            $table->index(['team_id', 'location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_stock_items');
    }
};
