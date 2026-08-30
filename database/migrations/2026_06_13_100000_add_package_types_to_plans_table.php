<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('package_type', 32)->default('pppoe')->after('description');
            $table->string('validity_period', 16)->nullable()->after('duration_days');
            $table->boolean('is_one_time')->default(false)->after('is_active');
            $table->boolean('is_recurring')->default(true)->after('is_one_time');
            $table->string('service_type', 64)->nullable()->after('is_recurring');
            $table->boolean('requires_pppoe_plan')->default(false)->after('service_type');
            $table->index(['isp_id', 'package_type']);
        });

        if (Schema::hasColumn('plans', 'access_type')) {
            DB::table('plans')->update([
                'package_type' => DB::raw('access_type'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex(['isp_id', 'package_type']);
            $table->dropColumn([
                'package_type',
                'validity_period',
                'is_one_time',
                'is_recurring',
                'service_type',
                'requires_pppoe_plan',
            ]);
        });
    }
};
