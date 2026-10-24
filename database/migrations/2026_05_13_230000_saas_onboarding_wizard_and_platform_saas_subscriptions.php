<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_saas_subscriptions')) {
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
        }

        Schema::table('i_s_p_s', function (Blueprint $table) {
            if (! Schema::hasColumn('i_s_p_s', 'saas_onboarding_step')) $table->unsignedTinyInteger('saas_onboarding_step')->default(1);
            if (! Schema::hasColumn('i_s_p_s', 'saas_pending_platform_plan_id')) $table->foreignId('saas_pending_platform_plan_id')->nullable();
            if (! Schema::hasColumn('i_s_p_s', 'support_phone')) $table->string('support_phone', 32)->nullable();
            if (! Schema::hasColumn('i_s_p_s', 'saas_payment_verified_at')) $table->timestamp('saas_payment_verified_at')->nullable();
            if (! Schema::hasColumn('i_s_p_s', 'saas_c2b_registered_at')) $table->timestamp('saas_c2b_registered_at')->nullable();
        });

        $hasForeignKey = collect(Schema::getForeignKeys('i_s_p_s'))
            ->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['saas_pending_platform_plan_id']);
        if (Schema::hasColumn('i_s_p_s', 'saas_pending_platform_plan_id') && ! $hasForeignKey) {
            Schema::table('i_s_p_s', fn (Blueprint $table) => $table->foreign('saas_pending_platform_plan_id')->references('id')->on('platform_saas_plans')->nullOnDelete());
        }
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
