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
        if (! Schema::hasTable('isp_payment_methods')) {
        Schema::create('isp_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->string('provider')->default('daraja'); // daraja, selcom, etc.
            $table->text('credentials'); // Encrypted JSON
            $table->string('callback_url')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
            
            $table->index(['isp_id', 'provider']);
            $table->index('active');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isp_payment_methods');
    }
};





