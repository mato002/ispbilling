<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Key/value store for platform-wide settings (commission %, SaaS grace days, etc.).
     * May be missing if an older migration batch failed or if `platform_settings` was
     * renamed to `platform_integrations` while still holding the integrations schema.
     */
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            if (! Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
            }
        }

        if (! Schema::hasTable('platform_settings') || ! Schema::hasColumn('platform_settings', 'key')) {
            return;
        }

        $now = now();

        DB::table('platform_settings')->updateOrInsert(
            ['key' => 'saas_payment_grace_days'],
            ['value' => '3', 'created_at' => $now, 'updated_at' => $now]
        );

        if (! DB::table('platform_settings')->where('key', 'platform_commission_percent')->exists()) {
            DB::table('platform_settings')->insert([
                'key' => 'platform_commission_percent',
                'value' => '0',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally no-op: do not drop a table that may already hold production data.
    }
};
