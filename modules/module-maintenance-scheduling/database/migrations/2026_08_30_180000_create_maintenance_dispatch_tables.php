<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_scheduling_engineer_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 128);
            $table->unsignedTinyInteger('proficiency')->default(1);
            $table->date('expires_on')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'user_id', 'name']);
            $table->index(['team_id', 'name']);
        });

        Schema::create('maintenance_shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 128);
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('timezone', 64)->default('UTC');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['team_id', 'user_id', 'weekday']);
        });

        Schema::create('maintenance_travel_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('schedule_entry_id')->constrained('maintenance_schedule_entries')->cascadeOnDelete();
            $table->string('origin', 255);
            $table->string('destination', 255);
            $table->unsignedInteger('planned_minutes')->nullable();
            $table->unsignedInteger('actual_minutes')->nullable();
            $table->string('status')->default('planned');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'schedule_entry_id']);
        });

        Schema::create('maintenance_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('schedule_entry_id')->constrained('maintenance_schedule_entries')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('dispatched_by')->nullable();
            $table->string('status')->default('offered');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['schedule_entry_id', 'user_id']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_dispatches');
        Schema::dropIfExists('maintenance_travel_segments');
        Schema::dropIfExists('maintenance_shifts');
        Schema::dropIfExists('maintenance_scheduling_engineer_skills');
    }
};
