<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('purchase_request_id')->nullable()->constrained('maintenance_purchase_requests')->nullOnDelete();
            $table->string('order_number');
            $table->string('supplier_name')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft');
            $table->dateTime('ordered_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->json('items')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'order_number']);
            $table->index(['team_id', 'status']);
        });

        Schema::create('maintenance_purchase_order_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('maintenance_purchase_orders')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('received_at');
            $table->json('items')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'purchase_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_purchase_order_receipts');
        Schema::dropIfExists('maintenance_purchase_orders');
    }
};
