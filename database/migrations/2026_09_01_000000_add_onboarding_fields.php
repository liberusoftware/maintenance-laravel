<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('onboarding_completed_at')->nullable();
        });

        // Existing accounts have already completed registration; only accounts
        // created after this migration should enter the guided setup flow.
        DB::table('users')->whereNull('onboarding_completed_at')->update([
            'onboarding_completed_at' => now(),
        ]);

        Schema::table('teams', function (Blueprint $table): void {
            $table->json('settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teams', fn (Blueprint $table) => $table->dropColumn('settings'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('onboarding_completed_at'));
    }
};
