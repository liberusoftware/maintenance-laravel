<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_compliance_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('compliance_record_id')->constrained('maintenance_compliance_records')->cascadeOnDelete();
            $table->string('kind', 80);
            $table->string('label');
            $table->string('reference')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'compliance_record_id']);
        });

        Schema::create('maintenance_compliance_corrective_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('compliance_record_id')->constrained('maintenance_compliance_records')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 40)->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'compliance_record_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_compliance_corrective_actions');
        Schema::dropIfExists('maintenance_compliance_evidence');
    }
};
