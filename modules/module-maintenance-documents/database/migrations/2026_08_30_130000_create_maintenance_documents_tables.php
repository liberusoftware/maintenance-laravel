<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('document_type')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('version')->default('1.0');
            $table->string('status')->default('draft');
            $table->string('compliance_standard')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('review_date')->nullable();
            $table->string('approval_status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->nullableMorphs('documentable');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['team_id', 'status']);
            $table->index(['expiry_date', 'status']);
        });

        Schema::create('maintenance_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('maintenance_documents')->cascadeOnDelete();
            $table->string('version');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('change_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['document_id', 'version']);
        });

        Schema::create('maintenance_document_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('color')->default('#3b82f6');
            $table->timestamps();
            $table->unique(['team_id', 'name']);
            $table->unique(['team_id', 'slug']);
        });

        Schema::create('maintenance_document_tag', function (Blueprint $table): void {
            $table->foreignId('document_id')->constrained('maintenance_documents')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('maintenance_document_tags')->cascadeOnDelete();
            $table->primary(['document_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_document_tag');
        Schema::dropIfExists('maintenance_document_tags');
        Schema::dropIfExists('maintenance_document_versions');
        Schema::dropIfExists('maintenance_documents');
    }
};
