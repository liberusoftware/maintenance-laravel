<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('stock_item_id')->constrained('maintenance_stock_items')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('delta');
            $table->unsignedInteger('quantity_before');
            $table->unsignedInteger('quantity_after');
            $table->string('reason', 64)->default('adjustment');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'stock_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_stock_movements');
    }
};
