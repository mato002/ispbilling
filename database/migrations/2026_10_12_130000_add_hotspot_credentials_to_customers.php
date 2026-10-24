<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'hotspot_password')) $table->text('hotspot_password')->nullable();
            if (! Schema::hasColumn('customers', 'hotspot_mac_address')) $table->string('hotspot_mac_address', 32)->nullable();
            if (! Schema::hasColumn('customers', 'hotspot_login_url')) $table->string('hotspot_login_url', 2048)->nullable();
        });

        $indexed = collect(Schema::getIndexes('customers'))
            ->contains(fn (array $index): bool => ($index['columns'] ?? []) === ['hotspot_mac_address']);
        if (Schema::hasColumn('customers', 'hotspot_mac_address') && ! $indexed) {
            Schema::table('customers', fn (Blueprint $table) => $table->index('hotspot_mac_address'));
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['hotspot_mac_address']);
            $table->dropColumn(['hotspot_password', 'hotspot_mac_address', 'hotspot_login_url']);
        });
    }
};
