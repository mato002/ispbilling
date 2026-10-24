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
        if (! Schema::hasTable('portal_settings')) {
        Schema::create('portal_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isp_id')->constrained('i_s_p_s')->onDelete('cascade');
            $table->string('welcome_message')->default('Welcome to WiFi');
            $table->string('logo_url')->nullable();
            $table->string('background_type')->default('gradient'); // gradient, image, color
            $table->string('background_value')->default('linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
            $table->boolean('show_packages')->default(true);
            $table->boolean('show_payment_button')->default(true);
            $table->string('payment_button_text')->default('Buy Now');
            $table->string('theme_color')->default('#3B82F6');
            $table->json('custom_css')->nullable();
            $table->timestamps();
            
            $table->unique('isp_id');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_settings');
    }
};
