<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('hotspot_sessions')) {
        Schema::create('hotspot_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('username');
            $table->string('ip_address');
            $table->string('mac_address')->nullable();
            $table->string('mikrotik_session_id')->nullable();
            $table->bigInteger('bytes_uploaded')->default(0);
            $table->bigInteger('bytes_downloaded')->default(0);
            $table->integer('uptime_seconds')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', ['active', 'ended', 'terminated'])->default('active');
            $table->text('termination_reason')->nullable();
            $table->timestamps();

            $table->index(['isp_id', 'status']);
            $table->index(['router_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('started_at');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_sessions');
    }
};





