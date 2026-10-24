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
        if (! Schema::hasTable('customers')) {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->foreignId('router_id')->nullable()->constrained('routers')->onDelete('set null');
            $table->string('full_name');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->text('location');
            $table->enum('connection_type', ['hotspot', 'pppoe'])->default('pppoe');
            $table->string('username')->unique();
            $table->string('password');
            $table->enum('status', ['active', 'suspended', 'expired', 'inactive'])->default('inactive');
            $table->date('installation_date');
            $table->string('mikrotik_user_id')->nullable(); // Store MikroTik user ID
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['isp_id', 'status']);
            $table->index('username');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
