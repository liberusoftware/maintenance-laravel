<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['team_id', 'code']);
            $table->index(['team_id', 'is_active']);
        });
        Schema::create('maintenance_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('maintenance_customers')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->text('address')->nullable();
            $table->text('access_details')->nullable();
            $table->text('hazards')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['team_id', 'code']);
            $table->index(['team_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_sites');
        Schema::dropIfExists('maintenance_customers');
    }
};
