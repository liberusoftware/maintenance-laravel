<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_work_order_dependencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained('maintenance_work_orders')->cascadeOnDelete();
            $table->foreignId('depends_on_work_order_id')->constrained('maintenance_work_orders')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['work_order_id', 'depends_on_work_order_id']);
            $table->index(['team_id', 'work_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_work_order_dependencies');
    }
};
