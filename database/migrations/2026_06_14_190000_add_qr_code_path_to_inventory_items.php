<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_items') && ! Schema::hasColumn('inventory_items', 'qr_code_path')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->string('qr_code_path')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_items') && Schema::hasColumn('inventory_items', 'qr_code_path')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropColumn('qr_code_path');
            });
        }
    }
};
