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
        if (! Schema::hasTable('router_action_logs')) {
        Schema::create('router_action_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // create_user, enable_user, disable_user, remove_user, disconnect, etc.
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->text('description');
            $table->text('error_message')->nullable();
            $table->json('request_data')->nullable(); // Router API request
            $table->json('response_data')->nullable(); // Router API response
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['isp_id', 'created_at']);
            $table->index(['router_id', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index('action');
            $table->index('status');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_action_logs');
    }
};
