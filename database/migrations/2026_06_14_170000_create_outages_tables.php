<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('outages')) {
            if (! Schema::hasTable('outages')) {
            Schema::create('outages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->enum('status', ['active', 'resolved'])->default('active');
                $table->timestamps();

                $table->index(['isp_id', 'status']);
                $table->index(['router_id', 'status']);
            });
            }
        }

        if (! Schema::hasTable('outage_compensation_logs')) {
            if (! Schema::hasTable('outage_compensation_logs')) {
            Schema::create('outage_compensation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outage_id')->constrained('outages')->cascadeOnDelete();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->enum('compensation_type', ['subscription_extension', 'voucher_extension']);
                $table->unsignedInteger('duration_value');
                $table->enum('duration_unit', ['hours', 'days']);
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
                $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
                $table->string('previous_expires_at')->nullable();
                $table->string('new_expires_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['outage_id', 'customer_id']);
                $table->index(['isp_id', 'created_at']);
            });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outage_compensation_logs');
        Schema::dropIfExists('outages');
    }
};
