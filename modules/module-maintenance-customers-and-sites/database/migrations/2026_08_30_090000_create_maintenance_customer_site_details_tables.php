<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('maintenance_customers')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'customer_id', 'is_active']);
        });

        Schema::create('maintenance_site_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('maintenance_sites')->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->text('access_details')->nullable();
            $table->text('hazards')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['site_id', 'name']);
            $table->index(['team_id', 'site_id', 'is_active']);
        });

        Schema::create('maintenance_site_service_windows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('maintenance_sites')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('timezone')->default('UTC');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->index(['team_id', 'site_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_site_service_windows');
        Schema::dropIfExists('maintenance_site_locations');
        Schema::dropIfExists('maintenance_contacts');
    }
};
