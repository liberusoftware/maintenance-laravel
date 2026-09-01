<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_preventative_plans', function (Blueprint $table): void {
            $table->string('priority', 32)->default('normal')->after('estimated_duration');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_preventative_plans', function (Blueprint $table): void {
            $table->dropColumn('priority');
        });
    }
};
