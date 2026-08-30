<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_availability_windows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('timezone')->default('UTC');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->index(['team_id', 'user_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_availability_windows');
    }
};
