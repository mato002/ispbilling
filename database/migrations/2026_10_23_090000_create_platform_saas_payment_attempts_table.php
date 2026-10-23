<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_saas_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
            $table->foreignId('platform_saas_invoice_id')->constrained('platform_saas_invoices')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status', 20)->default('pending');
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->text('authorization_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['isp_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_saas_payment_attempts');
    }
};
