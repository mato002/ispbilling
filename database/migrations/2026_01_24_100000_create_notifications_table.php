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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['success', 'warning', 'info', 'error', 'payment', 'subscription', 'system', 'hotspot'])->default('info');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->index(['isp_id', 'is_read']);
            $table->index(['isp_id', 'type']);
            $table->index(['isp_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};





