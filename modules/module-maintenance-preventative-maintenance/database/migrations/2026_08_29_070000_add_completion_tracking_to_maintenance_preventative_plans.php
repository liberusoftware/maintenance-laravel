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
            $table->dateTime('last_completed_at')->nullable()->after('next_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_preventative_plans', function (Blueprint $table): void {
            $table->dropColumn('last_completed_at');
        });
    }
};
