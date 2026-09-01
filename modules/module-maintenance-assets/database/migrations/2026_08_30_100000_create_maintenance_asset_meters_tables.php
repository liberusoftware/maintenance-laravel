<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_asset_meters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('maintenance_assets')->cascadeOnDelete();
            $table->string('name');
            $table->string('unit', 32);
            $table->decimal('current_value', 20, 6)->nullable();
            $table->timestamp('last_reading_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['asset_id', 'name']);
            $table->index(['team_id', 'asset_id', 'is_active']);
        });

        Schema::create('maintenance_asset_meter_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('maintenance_assets')->cascadeOnDelete();
            $table->foreignId('meter_id')->constrained('maintenance_asset_meters')->cascadeOnDelete();
            $table->decimal('value', 20, 6);
            $table->timestamp('recorded_at');
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'meter_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_asset_meter_readings');
        Schema::dropIfExists('maintenance_asset_meters');
    }
};
