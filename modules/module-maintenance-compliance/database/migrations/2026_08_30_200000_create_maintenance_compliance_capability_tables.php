<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $this->createTable('maintenance_compliance_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 40)->default('active');
            $table->dateTime('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'code']);
            $table->index(['team_id', 'status', 'expires_at']);
        });

        $this->createTable('maintenance_compliance_permits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('number', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 40)->default('active');
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'number']);
            $table->index(['team_id', 'status', 'expires_at']);
        });

        foreach (['risk_assessments', 'incidents'] as $tableName) {
            $this->createTable("maintenance_compliance_{$tableName}", function (Blueprint $table): void {
                $table->id();
                $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status', 40)->default('open');
                $table->string('severity', 40)->nullable();
                $table->unsignedTinyInteger('score')->nullable();
                $table->dateTime('occurred_at')->nullable();
                $table->dateTime('reviewed_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['team_id', 'status']);
                $table->index(['team_id', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_compliance_incidents');
        Schema::dropIfExists('maintenance_compliance_risk_assessments');
        Schema::dropIfExists('maintenance_compliance_permits');
        Schema::dropIfExists('maintenance_compliance_requirements');
    }

    private function createTable(string $name, callable $callback): void
    {
        Schema::create($name, $callback);
    }
};
