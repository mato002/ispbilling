<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->nullable()->constrained('i_s_p_s')->cascadeOnDelete();
            $table->string('form_key');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['isp_id', 'form_key'], 'form_settings_isp_form_unique');
        });

        Schema::create('form_field_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_setting_id')->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_hidden')->default(false);
            $table->json('default_value')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_setting_id', 'field_key'], 'form_field_settings_form_field_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_field_settings');
        Schema::dropIfExists('form_settings');
    }
};
