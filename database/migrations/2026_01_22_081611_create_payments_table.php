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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->string('payment_reference')->unique();
            $table->enum('payment_method', ['mpesa', 'selcom', 'manual', 'bank_transfer', 'voucher'])->default('manual');
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable(); // MPesa/Selcom transaction ID
            $table->string('phone_number')->nullable(); // For MPesa
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store gateway response
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['isp_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
