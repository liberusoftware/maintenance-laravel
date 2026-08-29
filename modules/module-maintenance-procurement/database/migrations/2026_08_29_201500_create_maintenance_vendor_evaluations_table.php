<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_vendor_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('vendor_contract_id')->nullable()->constrained('maintenance_vendor_contracts')->nullOnDelete();
            $table->string('vendor_name');
            $table->date('evaluation_date');
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('quality_rating')->default(0);
            $table->unsignedTinyInteger('timeliness_rating')->default(0);
            $table->unsignedTinyInteger('communication_rating')->default(0);
            $table->unsignedTinyInteger('cost_effectiveness_rating')->default(0);
            $table->unsignedTinyInteger('professionalism_rating')->default(0);
            $table->decimal('overall_rating', 3, 2)->default(0);
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('would_recommend')->default(true);
            $table->timestamps();
            $table->index(['team_id', 'vendor_name', 'evaluation_date']);
            $table->index(['team_id', 'overall_rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_vendor_evaluations');
    }
};
