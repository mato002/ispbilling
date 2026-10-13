<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('time_limit_minutes')->nullable()->after('time_limit_hours');
            $table->boolean('is_popular')->default(false)->after('customer_visible');
            $table->boolean('is_best_value')->default(false)->after('is_popular');
        });

        Schema::table('portal_settings', function (Blueprint $table) {
            $table->string('mpesa_till_number', 20)->nullable()->after('payment_button_text');
            $table->string('support_phone', 30)->nullable()->after('mpesa_till_number');
            $table->string('support_whatsapp', 30)->nullable()->after('support_phone');
        });
    }

    public function down(): void
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            $table->dropColumn(['mpesa_till_number', 'support_phone', 'support_whatsapp']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['time_limit_minutes', 'is_popular', 'is_best_value']);
        });
    }
};
