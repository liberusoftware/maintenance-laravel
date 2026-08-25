<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('title');
            $table->string('template_key')->nullable();
            $table->string('status')->default('draft');
            $table->string('outcome')->default('pending');
            $table->dateTime('inspected_at')->nullable();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('readings')->nullable();
            $table->json('failures')->nullable();
            $table->text('signature')->nullable();
            $table->string('certificate')->nullable();
            $table->json('follow_up')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'inspected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_inspections');
    }
};
