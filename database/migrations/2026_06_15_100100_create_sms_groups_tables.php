<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['isp_id', 'name']);
        });

        Schema::create('sms_group_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_group_id')->constrained('sms_groups')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sms_group_id', 'customer_id']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_group_customer');
        Schema::dropIfExists('sms_groups');
    }
};
