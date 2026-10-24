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
        if (Schema::hasTable('vouchers')) {
            return;
        }

        if (! Schema::hasTable('vouchers')) {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained()->onDelete('set null');
            $table->string('code', 20)->unique();
            $table->string('package_type'); // 'hour', 'daily', 'weekly', 'monthly', 'custom'
            $table->string('package_name'); // e.g., "1 Hour", "Daily", "Weekly"
            $table->integer('duration_hours')->nullable(); // Duration in hours
            $table->integer('data_limit_mb')->nullable(); // Data limit in MB
            $table->decimal('price', 10, 2)->nullable(); // Price if sold
            $table->enum('status', ['active', 'used', 'expired', 'disabled'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('used_by_ip')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['isp_id', 'status']);
            $table->index(['code']);
            $table->index(['expires_at']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
