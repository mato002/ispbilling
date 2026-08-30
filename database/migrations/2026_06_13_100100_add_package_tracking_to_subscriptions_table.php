<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('data_used_mb')->default(0)->after('auto_renew');
            $table->timestamp('data_exhausted_at')->nullable()->after('data_used_mb');
            $table->string('static_ip', 45)->nullable()->after('data_exhausted_at');
            $table->boolean('static_ip_active')->default(false)->after('static_ip');
            $table->string('service_status', 32)->nullable()->after('static_ip_active');
            $table->foreignId('parent_subscription_id')->nullable()->after('service_status')
                ->constrained('subscriptions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_subscription_id');
            $table->dropColumn([
                'data_used_mb',
                'data_exhausted_at',
                'static_ip',
                'static_ip_active',
                'service_status',
            ]);
        });
    }
};
