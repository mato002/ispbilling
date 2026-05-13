<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            // Asset / CMDB
            $table->string('vendor')->nullable()->after('location');
            $table->string('model')->nullable()->after('vendor');
            $table->string('serial_number')->nullable()->after('model');
            $table->string('mac_address')->nullable()->after('serial_number');
            $table->decimal('latitude', 10, 7)->nullable()->after('mac_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('branch')->nullable()->after('longitude');
            $table->string('tags')->nullable()->after('branch'); // comma-separated
            $table->foreignId('owner_user_id')->nullable()->after('tags')->constrained('users')->nullOnDelete();
            $table->string('lifecycle_status')->default('live')->after('owner_user_id'); // stock|live|maintenance|retired
            $table->date('purchase_date')->nullable()->after('lifecycle_status');
            $table->date('warranty_expiry')->nullable()->after('purchase_date');
            $table->decimal('monthly_cost', 12, 2)->nullable()->after('warranty_expiry');

            // Cached telemetry (last successful poll) — read by the NOC view
            $table->string('reachability_status')->default('unknown')->after('monthly_cost'); // online|degraded|offline|unknown|rebooting
            $table->timestamp('last_seen_at')->nullable()->after('reachability_status');
            $table->timestamp('last_telemetry_at')->nullable()->after('last_seen_at');
            $table->unsignedInteger('last_latency_ms')->nullable()->after('last_telemetry_at');
            $table->unsignedTinyInteger('last_health_pct')->nullable()->after('last_latency_ms');
            $table->unsignedTinyInteger('last_cpu_pct')->nullable()->after('last_health_pct');
            $table->unsignedTinyInteger('last_mem_pct')->nullable()->after('last_cpu_pct');
            $table->unsignedBigInteger('last_uptime_seconds')->nullable()->after('last_mem_pct');
            $table->string('last_firmware')->nullable()->after('last_uptime_seconds');
            $table->string('last_board')->nullable()->after('last_firmware');
            $table->unsignedInteger('last_active_hotspot_users')->nullable()->after('last_board');
            $table->unsignedInteger('last_active_pppoe_users')->nullable()->after('last_active_hotspot_users');
            $table->unsignedSmallInteger('last_interfaces_up')->nullable()->after('last_active_pppoe_users');
            $table->unsignedSmallInteger('last_interfaces_total')->nullable()->after('last_interfaces_up');
            $table->string('last_poll_error')->nullable()->after('last_interfaces_total');

            $table->index('reachability_status');
            $table->index('branch');
            $table->index('lifecycle_status');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropIndex(['reachability_status']);
            $table->dropIndex(['branch']);
            $table->dropIndex(['lifecycle_status']);
            $table->dropColumn([
                'vendor', 'model', 'serial_number', 'mac_address',
                'latitude', 'longitude', 'branch', 'tags', 'owner_user_id',
                'lifecycle_status', 'purchase_date', 'warranty_expiry', 'monthly_cost',
                'reachability_status', 'last_seen_at', 'last_telemetry_at',
                'last_latency_ms', 'last_health_pct', 'last_cpu_pct', 'last_mem_pct',
                'last_uptime_seconds', 'last_firmware', 'last_board',
                'last_active_hotspot_users', 'last_active_pppoe_users',
                'last_interfaces_up', 'last_interfaces_total', 'last_poll_error',
            ]);
        });
    }
};
