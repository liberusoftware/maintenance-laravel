<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_compliance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('kind', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('status', 40)->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_compliance_records');
    }
};
