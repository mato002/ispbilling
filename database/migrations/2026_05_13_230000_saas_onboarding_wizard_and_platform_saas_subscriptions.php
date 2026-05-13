<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_saas_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->unique()->constrained('i_s_p_s')->cascadeOnDelete();
            $table->foreignId('platform_saas_plan_id')->nullable()->constrained('platform_saas_plans')->nullOnDelete();
            $table->string('status', 32)->default('trialing');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('i_s_p_s', function (Blueprint $table) {
            $table->unsignedTinyInteger('saas_onboarding_step')->default(1)->after('saas_onboarding_completed_at');
            $table->foreignId('saas_pending_platform_plan_id')->nullable()->after('saas_onboarding_step')->constrained('platform_saas_plans')->nullOnDelete();
            $table->string('support_phone', 32)->nullable()->after('phone');
            $table->timestamp('saas_payment_verified_at')->nullable()->after('saas_billing_phone');
            $table->timestamp('saas_c2b_registered_at')->nullable()->after('saas_payment_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('i_s_p_s', function (Blueprint $table) {
            $table->dropConstrainedForeignId('saas_pending_platform_plan_id');
            $table->dropColumn([
                'saas_onboarding_step',
                'support_phone',
                'saas_payment_verified_at',
                'saas_c2b_registered_at',
            ]);
        });

        Schema::dropIfExists('platform_saas_subscriptions');
    }
};
