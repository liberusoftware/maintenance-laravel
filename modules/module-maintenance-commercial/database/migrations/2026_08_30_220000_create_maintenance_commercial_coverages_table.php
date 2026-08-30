<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_commercial_coverages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('commercial_record_id')->constrained('maintenance_commercial_records')->cascadeOnDelete();
            $table->foreignId('covered_asset_id')->nullable()->constrained('maintenance_assets')->nullOnDelete();
            $table->string('service_name')->nullable();
            $table->string('coverage_type')->default('service');
            $table->decimal('rate', 14, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->unsignedInteger('sla_hours')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->date('renewal_on')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'commercial_record_id']);
            $table->index(['team_id', 'renewal_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_commercial_coverages');
    }
};
