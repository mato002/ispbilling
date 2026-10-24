<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'time_limit_minutes')) $table->unsignedInteger('time_limit_minutes')->nullable();
            if (! Schema::hasColumn('plans', 'is_popular')) $table->boolean('is_popular')->default(false);
            if (! Schema::hasColumn('plans', 'is_best_value')) $table->boolean('is_best_value')->default(false);
        });

        Schema::table('portal_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('portal_settings', 'mpesa_till_number')) $table->string('mpesa_till_number', 20)->nullable();
            if (! Schema::hasColumn('portal_settings', 'support_phone')) $table->string('support_phone', 30)->nullable();
            if (! Schema::hasColumn('portal_settings', 'support_whatsapp')) $table->string('support_whatsapp', 30)->nullable();
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
