<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_purchase_order_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('maintenance_purchase_orders')->cascadeOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('requested');
            $table->dateTime('returned_at')->nullable();
            $table->json('items');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'purchase_order_id', 'status']);
        });

        Schema::create('maintenance_purchase_order_cost_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('maintenance_purchase_orders')->cascadeOnDelete();
            $table->string('cost_center');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'purchase_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_purchase_order_cost_allocations');
        Schema::dropIfExists('maintenance_purchase_order_returns');
    }
};
