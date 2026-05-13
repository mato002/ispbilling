<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only rename legacy integrations table: do not touch key/value `platform_settings`
        // (id, key, value) used by PlatformSetting — it must never have a `slug` column.
        if (Schema::hasTable('platform_settings')
            && Schema::hasColumn('platform_settings', 'slug')
            && ! Schema::hasColumn('platform_settings', 'key')) {
            Schema::rename('platform_settings', 'platform_integrations');

            return;
        }

        if (Schema::hasTable('platform_integrations')) {
            return;
        }

        Schema::create('platform_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('payment_pipes')->nullable();
            $table->json('global_billing')->nullable();
            $table->text('credentials_vault')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_integrations')) {
            return;
        }

        if (Schema::hasColumn('platform_integrations', 'slug') && ! Schema::hasTable('platform_settings')) {
            Schema::rename('platform_integrations', 'platform_settings');

            return;
        }

        Schema::dropIfExists('platform_integrations');
    }
};
