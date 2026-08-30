<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings') || ! Schema::hasColumn('platform_settings', 'key')) {
            return;
        }

        $now = now();

        $defaults = [
            'saas_default_trial_days' => '14',
            'saas_lock_on_past_due' => '0',
            'saas_suspend_customer_services' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if (! DB::table('platform_settings')->where('key', $key)->exists()) {
                DB::table('platform_settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally no-op.
    }
};
