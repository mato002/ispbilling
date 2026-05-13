<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('i_s_p_s', function (Blueprint $table) {
            $table->boolean('platform_access_locked')->default(false)->after('is_active');
            $table->text('platform_access_message')->nullable()->after('platform_access_locked');
            $table->string('saas_tier', 32)->nullable()->after('platform_access_message');
        });

        Schema::create('global_promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('kind', 32); // first_month_free, referral, percent_off
            $table->string('title')->nullable();
            $table->decimal('percent_off', 5, 2)->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redemption_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('platform_integrations') && ! Schema::hasColumn('platform_integrations', 'messaging_channels')) {
            Schema::table('platform_integrations', function (Blueprint $table) {
                $table->text('messaging_channels')->nullable()->after('credentials_vault');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_integrations') && Schema::hasColumn('platform_integrations', 'messaging_channels')) {
            Schema::table('platform_integrations', function (Blueprint $table) {
                $table->dropColumn('messaging_channels');
            });
        }

        Schema::dropIfExists('global_promo_codes');

        Schema::table('i_s_p_s', function (Blueprint $table) {
            $table->dropColumn(['platform_access_locked', 'platform_access_message', 'saas_tier']);
        });
    }
};
