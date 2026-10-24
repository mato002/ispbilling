<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            if (! Schema::hasColumn('routers', 'hotspot_gateway_ip')) {
                $table->string('hotspot_gateway_ip', 45)->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('routers', 'hotspot_gateway_ip')) {
            Schema::table('routers', fn (Blueprint $table) => $table->dropColumn('hotspot_gateway_ip'));
        }
    }
};
