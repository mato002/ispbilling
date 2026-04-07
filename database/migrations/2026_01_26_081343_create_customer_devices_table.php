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
        Schema::create('customer_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('mac_address', 17)->unique()->comment('MAC address of the device');
            $table->string('device_name')->nullable()->comment('Optional friendly name for the device');
            $table->string('device_type')->nullable()->comment('e.g., phone, laptop, tablet');
            $table->boolean('is_active')->default(true)->comment('Whether this device is currently allowed');
            $table->boolean('is_blocked')->default(false)->comment('Whether this device is blocked');
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();

            // Index for faster lookups
            $table->index(['customer_id', 'is_active']);
            $table->index('mac_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_devices');
    }
};
