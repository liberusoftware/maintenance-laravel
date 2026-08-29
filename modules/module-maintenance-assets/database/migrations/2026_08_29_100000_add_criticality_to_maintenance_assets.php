<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_assets', function (Blueprint $table): void {
            $table->string('criticality', 32)->default('normal')->after('condition');
            $table->index(['team_id', 'criticality']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_assets', function (Blueprint $table): void {
            $table->dropIndex('maintenance_assets_team_id_criticality_index');
            $table->dropColumn('criticality');
        });
    }
};
