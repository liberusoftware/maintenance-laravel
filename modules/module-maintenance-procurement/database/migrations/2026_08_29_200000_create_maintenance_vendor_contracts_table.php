<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_vendor_contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('vendor_name');
            $table->string('contract_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('contract_type')->default('service');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('contract_value', 14, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('draft');
            $table->boolean('auto_renewal')->default(false);
            $table->date('renewal_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'contract_number']);
            $table->index(['team_id', 'status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_vendor_contracts');
    }
};
