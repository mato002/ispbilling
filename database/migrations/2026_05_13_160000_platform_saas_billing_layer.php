<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_saas_plans')) {
            Schema::create('platform_saas_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug', 64)->unique();
                $table->text('description')->nullable();
                $table->unsignedInteger('max_customers')->nullable();
                $table->decimal('monthly_price', 12, 2)->default(0);
                $table->decimal('setup_fee', 12, 2)->default(0);
                $table->unsignedSmallInteger('trial_days')->default(14);
                $table->json('features')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('platform_saas_invoices')) {
            Schema::create('platform_saas_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->foreignId('platform_saas_plan_id')->nullable()->constrained('platform_saas_plans')->nullOnDelete();
                $table->string('invoice_number', 48)->unique();
                $table->decimal('subscription_amount', 12, 2)->default(0);
                $table->decimal('setup_fee_amount', 12, 2)->default(0);
                $table->string('currency', 3)->default('KES');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->date('due_date')->nullable();
                $table->string('status', 24)->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['isp_id', 'status']);
                $table->index('due_date');
            });
        }

        if (! Schema::hasColumn('i_s_p_s', 'platform_saas_plan_id')) {
            Schema::table('i_s_p_s', function (Blueprint $table) {
                $table->foreignId('platform_saas_plan_id')->nullable()->constrained('platform_saas_plans')->nullOnDelete();
                $table->string('saas_subscription_status', 24)->default('none')->after('platform_saas_plan_id');
                $table->timestamp('saas_trial_ends_at')->nullable()->after('saas_subscription_status');
                $table->date('saas_current_period_start')->nullable()->after('saas_trial_ends_at');
                $table->date('saas_current_period_end')->nullable()->after('saas_current_period_start');
                $table->timestamp('saas_next_billing_at')->nullable()->after('saas_current_period_end');
                $table->timestamp('saas_grace_ends_at')->nullable()->after('saas_next_billing_at');
                $table->string('saas_billing_phone', 20)->nullable()->after('saas_grace_ends_at');
                $table->string('saas_company_pin', 32)->nullable()->after('saas_billing_phone');
                $table->string('saas_billing_method', 24)->default('mpesa')->after('saas_company_pin');
                $table->timestamp('saas_onboarding_completed_at')->nullable()->after('saas_billing_method');
                $table->timestamp('saas_setup_fee_paid_at')->nullable()->after('saas_onboarding_completed_at');
                $table->boolean('saas_white_label_opt_in')->default(false)->after('saas_setup_fee_paid_at');
            });
        }

        if (Schema::hasTable('platform_saas_plans') && DB::table('platform_saas_plans')->count() === 0) {
            DB::table('platform_saas_plans')->insert([
                [
                    'name' => 'Basic',
                    'slug' => 'basic',
                    'description' => 'Up to 50 end-customer accounts.',
                    'max_customers' => 50,
                    'monthly_price' => 2499,
                    'setup_fee' => 1500,
                    'trial_days' => 14,
                    'features' => json_encode(['advanced_analytics' => false, 'sms_automation' => false]),
                    'sort_order' => 10,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Standard',
                    'slug' => 'standard',
                    'description' => 'Up to 500 end-customer accounts with advanced analytics.',
                    'max_customers' => 500,
                    'monthly_price' => 7999,
                    'setup_fee' => 3500,
                    'trial_days' => 14,
                    'features' => json_encode(['advanced_analytics' => true, 'sms_automation' => false]),
                    'sort_order' => 20,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Enterprise',
                    'slug' => 'enterprise',
                    'description' => 'Unlimited customers, analytics, and automated SMS alerts.',
                    'max_customers' => null,
                    'monthly_price' => 24999,
                    'setup_fee' => 7500,
                    'trial_days' => 7,
                    'features' => json_encode(['advanced_analytics' => true, 'sms_automation' => true]),
                    'sort_order' => 30,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (Schema::hasTable('platform_settings')) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => 'saas_payment_grace_days'],
                ['value' => '3', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        if (Schema::hasColumn('i_s_p_s', 'saas_onboarding_completed_at')) {
            DB::table('i_s_p_s')->whereNull('saas_onboarding_completed_at')->update([
                'saas_onboarding_completed_at' => now(),
            ]);
            DB::table('i_s_p_s')->where('saas_subscription_status', 'none')->update([
                'saas_subscription_status' => 'active',
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('i_s_p_s', 'platform_saas_plan_id')) {
            Schema::table('i_s_p_s', function (Blueprint $table) {
                $table->dropForeign(['platform_saas_plan_id']);
                $table->dropColumn([
                    'platform_saas_plan_id',
                    'saas_subscription_status',
                    'saas_trial_ends_at',
                    'saas_current_period_start',
                    'saas_current_period_end',
                    'saas_next_billing_at',
                    'saas_grace_ends_at',
                    'saas_billing_phone',
                    'saas_company_pin',
                    'saas_billing_method',
                    'saas_onboarding_completed_at',
                    'saas_setup_fee_paid_at',
                    'saas_white_label_opt_in',
                ]);
            });
        }

        Schema::dropIfExists('platform_saas_invoices');
        Schema::dropIfExists('platform_saas_plans');
    }
};
