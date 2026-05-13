<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('router_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained('routers')->cascadeOnDelete();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
            $table->timestamp('recorded_at')->index();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedTinyInteger('cpu_pct')->nullable();
            $table->unsignedTinyInteger('mem_pct')->nullable();
            $table->unsignedTinyInteger('health_pct')->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->unsignedInteger('active_hotspot_users')->nullable();
            $table->unsignedInteger('active_pppoe_users')->nullable();
            $table->unsignedBigInteger('rx_bytes_per_sec')->nullable();
            $table->unsignedBigInteger('tx_bytes_per_sec')->nullable();
            $table->unsignedSmallInteger('interfaces_up')->nullable();
            $table->unsignedSmallInteger('interfaces_total')->nullable();
            $table->string('reachability', 16)->default('online'); // online|degraded|offline
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['router_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('router_metrics');
    }
};
