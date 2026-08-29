<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_organizations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('name');
            $table->string('code', 32);
            $table->text('description')->nullable();
            $table->string('state', 20)->default('active');
            $table->timestamps();
            $table->unique(['team_id', 'code']);
            $table->index(['team_id', 'state']);
        });
        foreach (['statuses', 'priorities'] as $type) {
            Schema::create("maintenance_{$type}", function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('team_id');
                $table->string('name');
                $table->string('code', 32);
                $table->string('color', 32)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['team_id', 'code']);
                $table->index(['team_id', 'is_active', 'sort_order']);
            });
        }
        Schema::create('maintenance_numbering_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('document_type', 64);
            $table->string('prefix', 32);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->timestamps();
            $table->unique(['team_id', 'document_type']);
        });
        Schema::create('maintenance_service_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('key', 128);
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            $table->unique(['team_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_service_settings');
        Schema::dropIfExists('maintenance_numbering_sequences');
        Schema::dropIfExists('maintenance_priorities');
        Schema::dropIfExists('maintenance_statuses');
        Schema::dropIfExists('maintenance_organizations');
    }
};
