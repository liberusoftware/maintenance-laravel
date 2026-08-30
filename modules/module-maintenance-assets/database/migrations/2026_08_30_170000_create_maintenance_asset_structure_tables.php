<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_asset_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('maintenance_asset_categories')->nullOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['team_id', 'code']);
            $table->index(['team_id', 'parent_id']);
        });

        Schema::create('maintenance_asset_specifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('maintenance_assets')->cascadeOnDelete();
            $table->string('key', 128);
            $table->text('value');
            $table->string('unit', 32)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['asset_id', 'key']);
            $table->index(['team_id', 'asset_id']);
        });

        Schema::create('maintenance_asset_warranties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('maintenance_assets')->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('reference')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('expires_on');
            $table->text('terms')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['team_id', 'asset_id', 'expires_on']);
        });

        Schema::create('maintenance_asset_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('maintenance_assets')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 64);
            $table->text('note');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['team_id', 'asset_id', 'occurred_at']);
        });

        Schema::table('maintenance_assets', function (Blueprint $table): void {
            $table->foreignId('parent_id')->nullable()->after('team_id')->constrained('maintenance_assets')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('parent_id')->constrained('maintenance_asset_categories')->nullOnDelete();
            $table->index(['team_id', 'parent_id']);
            $table->index(['team_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_assets', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['category_id']);
            $table->dropIndex(['maintenance_assets_team_id_parent_id_index']);
            $table->dropIndex(['maintenance_assets_team_id_category_id_index']);
            $table->dropColumn(['parent_id', 'category_id']);
        });
        Schema::dropIfExists('maintenance_asset_history');
        Schema::dropIfExists('maintenance_asset_warranties');
        Schema::dropIfExists('maintenance_asset_specifications');
        Schema::dropIfExists('maintenance_asset_categories');
    }
};
