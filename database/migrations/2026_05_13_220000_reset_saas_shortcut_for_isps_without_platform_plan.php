<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Super-admin-created ISPs were previously marked onboarded and "active"
     * without a platform SaaS plan or billing capture. Reset those rows so
     * {@see \App\Models\ISP::needsSaasOnboarding()} applies like self-serve tenants.
     */
    public function up(): void
    {
        DB::table('i_s_p_s')
            ->whereNull('platform_saas_plan_id')
            ->whereNotNull('saas_onboarding_completed_at')
            ->update([
                'saas_onboarding_completed_at' => null,
                'saas_subscription_status' => 'none',
                'saas_trial_ends_at' => null,
                'saas_next_billing_at' => null,
                'saas_grace_ends_at' => null,
                'saas_current_period_start' => null,
                'saas_current_period_end' => null,
            ]);
    }

    public function down(): void
    {
        // Non-reversible: prior shortcut state is not recoverable.
    }
};
