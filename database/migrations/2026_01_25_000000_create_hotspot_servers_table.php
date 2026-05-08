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
        if (Schema::hasTable('hotspot_servers')) {
            return;
        }

        Schema::create('hotspot_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained()->onDelete('cascade');
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Hotspot server name (e.g., 'hotspot1', 'wifi_hotspot')
            $table->string('interface'); // Interface name (e.g., 'ether2', 'wlan1')
            $table->boolean('is_active')->default(false);
            
            // Hotspot server configuration
            $table->string('server_profile')->nullable();
            $table->string('default_profile')->nullable();
            $table->string('login_by')->default('cookie');
            $table->boolean('use_radius')->default(false);
            $table->boolean('radius_accounting')->default(false);
            $table->integer('mac_cookie_timeout')->default(300);
            $table->integer('idle_timeout')->default(300);
            $table->integer('keepalive_timeout')->default(60);
            $table->boolean('status_autologout')->default(false);
            $table->integer('allowed_users')->default(0);
            $table->integer('shared_users')->default(0);
            $table->string('mac_format')->nullable();
            $table->string('mac_password')->nullable();
            $table->string('radius_mac_format')->nullable();
            $table->string('radius_mac_password')->nullable();
            $table->integer('trial_uptime')->default(0);
            $table->integer('trial_uptime_reset')->default(0);
            $table->string('trial_profile')->nullable();
            $table->integer('trial_users')->default(0);
            
            // Configuration storage
            $table->json('config_file')->nullable();
            $table->json('config_backup')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['isp_id', 'is_active']);
            $table->index(['router_id', 'is_active']);
            $table->unique(['router_id', 'name'], 'unique_router_hotspot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_servers');
    }
};
