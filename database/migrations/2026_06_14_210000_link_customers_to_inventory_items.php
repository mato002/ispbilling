<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            if (! Schema::hasColumn('customers', 'inventory_item_id')) {
                Schema::table('customers', function (Blueprint $table) {
                    $table->foreignId('inventory_item_id')
                        ->nullable()
                        ->after('router_id')
                        ->constrained('inventory_items')
                        ->nullOnDelete();
                });
            }

            DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('active', 'suspended', 'expired', 'inactive', 'pending_installation') NOT NULL DEFAULT 'inactive'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'inventory_item_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('inventory_item_id');
            });
        }

        if (Schema::hasTable('customers')) {
            DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('active', 'suspended', 'expired', 'inactive') NOT NULL DEFAULT 'inactive'");
        }
    }
};
