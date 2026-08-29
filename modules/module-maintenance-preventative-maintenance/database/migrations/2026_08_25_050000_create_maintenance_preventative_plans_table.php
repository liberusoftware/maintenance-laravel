<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_preventative_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->string('frequency_unit')->default('days');
            $table->unsignedInteger('frequency_value');
            $table->dateTime('next_due_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('rules')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'code']);
            $table->index(['team_id', 'next_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_preventative_plans');
    }
};
