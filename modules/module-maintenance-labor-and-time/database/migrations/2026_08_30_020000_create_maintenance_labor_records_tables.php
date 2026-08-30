<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_engineer_skills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('skill', 120);
            $table->unsignedTinyInteger('level')->default(1);
            $table->date('certified_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'user_id', 'skill']);
        });

        Schema::create('maintenance_attendance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('clocked_in_at')->nullable();
            $table->dateTime('clocked_out_at')->nullable();
            $table->string('status', 32)->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'user_id', 'attendance_date']);
        });

        Schema::create('maintenance_labor_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->decimal('hourly_rate', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'effective_from']);
        });

        Schema::create('maintenance_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 32)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_expenses');
        Schema::dropIfExists('maintenance_labor_rates');
        Schema::dropIfExists('maintenance_attendance');
        Schema::dropIfExists('maintenance_engineer_skills');
    }
};
