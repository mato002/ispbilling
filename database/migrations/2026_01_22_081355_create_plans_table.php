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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('access_type', ['hotspot', 'pppoe'])->default('pppoe');
            $table->decimal('price', 10, 2);
            $table->integer('duration_days')->default(30);
            $table->string('speed_profile')->nullable(); // MikroTik profile name
            $table->integer('upload_speed_kbps')->nullable();
            $table->integer('download_speed_kbps')->nullable();
            $table->enum('billing_type', ['time', 'data', 'unlimited'])->default('unlimited');
            $table->bigInteger('data_limit_mb')->nullable(); // For data-based plans
            $table->integer('time_limit_hours')->nullable(); // For time-based plans
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
