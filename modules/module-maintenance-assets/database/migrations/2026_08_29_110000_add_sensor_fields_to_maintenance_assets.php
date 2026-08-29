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
            $table->boolean('sensor_enabled')->default(false)->after('barcode');
            $table->string('sensor_type')->nullable()->after('sensor_enabled');
            $table->string('sensor_id')->nullable()->after('sensor_type');
            $table->json('sensor_config')->nullable()->after('sensor_id');
            $table->dateTime('last_sensor_reading_at')->nullable()->after('sensor_config');
            $table->index(['team_id', 'sensor_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_assets', function (Blueprint $table): void {
            $table->dropIndex(['maintenance_assets_team_id_sensor_enabled_index']);
            $table->dropColumn(['sensor_enabled', 'sensor_type', 'sensor_id', 'sensor_config', 'last_sensor_reading_at']);
        });
    }
};
