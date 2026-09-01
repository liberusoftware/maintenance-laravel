<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_inspection_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('key', 120);
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('checklist')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['team_id', 'key']);
            $table->index(['team_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_inspection_templates');
    }
};
