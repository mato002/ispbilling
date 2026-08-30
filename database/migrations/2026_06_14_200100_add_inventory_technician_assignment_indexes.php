<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_technician_assignments')) {
            return;
        }

        $existing = collect(DB::select('SHOW INDEX FROM inventory_technician_assignments'))
            ->pluck('Key_name')
            ->unique();

        Schema::table('inventory_technician_assignments', function (Blueprint $table) use ($existing) {
            if (! $existing->contains('inv_tech_isp_user_active_idx')) {
                $table->index(['isp_id', 'technician_user_id', 'is_active'], 'inv_tech_isp_user_active_idx');
            }

            if (! $existing->contains('inv_tech_item_active_idx')) {
                $table->index(['inventory_item_id', 'is_active'], 'inv_tech_item_active_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_technician_assignments')) {
            return;
        }

        Schema::table('inventory_technician_assignments', function (Blueprint $table) {
            $table->dropIndex('inv_tech_isp_user_active_idx');
            $table->dropIndex('inv_tech_item_active_idx');
        });
    }
};
