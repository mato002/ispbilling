<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('network_audit_logs')) {
            return;
        }

        Schema::create('network_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('router_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->string('status', 32)->default('queued');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['isp_id', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_audit_logs');
    }
};
