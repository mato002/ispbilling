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
        if (Schema::hasTable('ip_pools')) {
            return;
        }

        Schema::create('ip_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->foreignId('router_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name'); // Pool name (e.g., 'hotspot_pool')
            $table->json('ranges'); // IP ranges array
            $table->string('next_pool')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('pool_type')->default('regular'); // regular, dhcp-only, etc.
            
            // DHCP configuration
            $table->string('gateway_address')->nullable();
            $table->string('netmask')->nullable();
            $table->json('dns_servers')->nullable();
            $table->string('domain')->nullable();
            $table->json('wins_servers')->nullable();
            $table->json('ntp_servers')->nullable();
            $table->integer('lease_time')->default(300);
            
            // Advanced settings
            $table->integer('block_size')->nullable();
            $table->integer('block_count')->nullable();
            $table->boolean('disabled')->default(false);
            
            // Configuration storage
            $table->json('config_file')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['isp_id', 'is_active']);
            $table->index(['router_id', 'is_active']);
            $table->unique(['isp_id', 'name'], 'unique_isp_pool_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_pools');
    }
};
