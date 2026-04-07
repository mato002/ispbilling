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
        Schema::create('payment_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->foreignId('isp_id')->nullable()->constrained('i_s_p_s')->onDelete('set null');
            $table->string('transaction_type'); // 'initiated', 'callback', 'webhook', 'verification', 'error'
            $table->string('payment_method'); // 'mpesa', 'selcom', 'manual'
            $table->string('payment_reference')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('status')->nullable(); // 'pending', 'completed', 'failed'
            $table->decimal('amount', 10, 2)->nullable();
            $table->text('request_data')->nullable(); // JSON string of request
            $table->text('response_data')->nullable(); // JSON string of response
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['payment_id', 'transaction_type']);
            $table->index(['isp_id', 'created_at']);
            $table->index('transaction_id');
            $table->index('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transaction_logs');
    }
};
