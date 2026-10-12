<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('hotspot_password')->nullable();
            $table->string('hotspot_mac_address', 32)->nullable()->index();
            $table->string('hotspot_login_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['hotspot_mac_address']);
            $table->dropColumn(['hotspot_password', 'hotspot_mac_address', 'hotspot_login_url']);
        });
    }
};
