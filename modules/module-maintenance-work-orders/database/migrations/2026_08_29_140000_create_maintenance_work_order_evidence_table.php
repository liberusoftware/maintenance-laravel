<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_work_order_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained('maintenance_work_orders')->cascadeOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 64);
            $table->string('label');
            $table->text('reference');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'work_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_work_order_evidence');
    }
};
