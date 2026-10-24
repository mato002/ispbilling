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
        if (! Schema::hasTable('payment_audit_logs')) {
        Schema::create('payment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // initiated, completed, failed, refunded, cancelled, etc.
            $table->enum('payment_method', ['mpesa', 'selcom', 'manual', 'bank_transfer'])->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->text('description');
            $table->text('error_message')->nullable();
            $table->json('gateway_response')->nullable(); // MPesa/Selcom response
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['isp_id', 'created_at']);
            $table->index(['payment_id', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index('action');
            $table->index('status');
            $table->index('transaction_id');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_audit_logs');
    }
};
