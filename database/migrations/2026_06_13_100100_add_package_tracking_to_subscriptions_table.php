<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'data_used_mb')) $table->unsignedBigInteger('data_used_mb')->default(0);
            if (! Schema::hasColumn('subscriptions', 'data_exhausted_at')) $table->timestamp('data_exhausted_at')->nullable();
            if (! Schema::hasColumn('subscriptions', 'static_ip')) $table->string('static_ip', 45)->nullable();
            if (! Schema::hasColumn('subscriptions', 'static_ip_active')) $table->boolean('static_ip_active')->default(false);
            if (! Schema::hasColumn('subscriptions', 'service_status')) $table->string('service_status', 32)->nullable();
            if (! Schema::hasColumn('subscriptions', 'parent_subscription_id')) $table->foreignId('parent_subscription_id')->nullable();
        });

        $hasForeignKey = collect(Schema::getForeignKeys('subscriptions'))
            ->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['parent_subscription_id']);
        if (Schema::hasColumn('subscriptions', 'parent_subscription_id') && ! $hasForeignKey) {
            Schema::table('subscriptions', fn (Blueprint $table) => $table->foreign('parent_subscription_id')->references('id')->on('subscriptions')->nullOnDelete());
        }
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
