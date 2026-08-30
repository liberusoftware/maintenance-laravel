<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_customers', function (Blueprint $table): void {
            $table->text('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('zip')->nullable()->after('state');
            $table->string('website')->nullable()->after('zip');
            $table->string('industry')->nullable()->after('website');
            $table->text('description')->nullable()->after('industry');
            $table->string('type')->default('customer')->after('description');
            $table->string('contact_person')->nullable()->after('type');
            $table->string('payment_terms')->nullable()->after('contact_person');
            $table->index(['team_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_customers', function (Blueprint $table): void {
            $table->dropIndex(['maintenance_customers_team_id_type_index']);
            $table->dropColumn(['address', 'city', 'state', 'zip', 'website', 'industry', 'description', 'type', 'contact_person', 'payment_terms']);
        });
    }
};
