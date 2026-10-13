<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('data_usages', 'subscription_id')) {
            Schema::table('data_usages', function (Blueprint $table) {
                $table->foreignId('subscription_id')->nullable()->after('package_id')
                    ->constrained('subscriptions')->nullOnDelete();
                $table->index(['subscription_id', 'source']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('data_usages', 'subscription_id')) {
            Schema::table('data_usages', function (Blueprint $table) {
                $table->dropIndex(['subscription_id', 'source']);
                $table->dropConstrainedForeignId('subscription_id');
            });
        }
    }
};
