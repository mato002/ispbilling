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
        if (! Schema::hasTable('customer_usage_stats')) {
        Schema::create('customer_usage_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->date('date');
            $table->bigInteger('bytes_uploaded')->default(0);
            $table->bigInteger('bytes_downloaded')->default(0);
            $table->bigInteger('total_bytes')->default(0);
            $table->integer('session_count')->default(0);
            $table->integer('total_uptime_seconds')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'date']);
            $table->index(['customer_id', 'date']);
            $table->index('date');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_usage_stats');
    }
};





