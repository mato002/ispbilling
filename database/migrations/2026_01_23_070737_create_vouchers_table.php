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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('voucher_code')->unique();
            $table->enum('voucher_type', ['time', 'data'])->default('time');
            $table->integer('time_limit_hours')->nullable(); // For time-based vouchers
            $table->bigInteger('data_limit_mb')->nullable(); // For data-based vouchers
            $table->string('speed_profile')->nullable(); // MikroTik profile name
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('used_by')->nullable()->constrained('customers')->onDelete('set null');
            $table->timestamp('used_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['isp_id', 'status']);
            $table->index(['router_id', 'status']);
            $table->index('voucher_code');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
