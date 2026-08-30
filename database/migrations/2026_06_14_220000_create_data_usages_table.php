<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->unsignedBigInteger('upload_bytes')->default(0);
            $table->unsignedBigInteger('download_bytes')->default(0);
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->timestamp('recorded_at');

            // Integration metadata for MikroTik / RADIUS / PPPoE sources
            $table->foreignId('hotspot_session_id')->nullable()->constrained('hotspot_sessions')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->string('source', 32)->default('hotspot');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('external_session_id', 64)->nullable();

            $table->timestamps();

            $table->index(['isp_id', 'recorded_at']);
            $table->index(['customer_id', 'recorded_at']);
            $table->index(['router_id', 'recorded_at']);
            $table->index(['package_id', 'recorded_at']);
            $table->unique('hotspot_session_id');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_usages');
    }
};
