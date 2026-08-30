<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_asset_sensor_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('maintenance_assets')->cascadeOnDelete();
            $table->string('sensor_type')->nullable();
            $table->string('metric_name', 120);
            $table->decimal('value', 18, 6);
            $table->string('unit', 32)->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 16)->default('normal');
            $table->dateTime('reading_at');
            $table->timestamps();
            $table->index(['asset_id', 'reading_at']);
            $table->index(['asset_id', 'metric_name', 'reading_at']);
            $table->index(['status', 'reading_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_asset_sensor_readings');
    }
};
