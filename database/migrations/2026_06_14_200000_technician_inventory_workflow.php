<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'isp_admin', 'isp_staff', 'isp_technician') NOT NULL DEFAULT 'isp_staff'");
        }

        if (! Schema::hasTable('inventory_technician_assignments')) {
            if (! Schema::hasTable('inventory_technician_assignments')) {
            Schema::create('inventory_technician_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->foreignId('technician_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at');
                $table->timestamp('returned_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['isp_id', 'technician_user_id', 'is_active'], 'inv_tech_isp_user_active_idx');
                $table->index(['inventory_item_id', 'is_active'], 'inv_tech_item_active_idx');
            });
            }
        }

        if (Schema::hasTable('inventory_transactions')) {
            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN action ENUM(
                'added',
                'assigned',
                'returned',
                'replaced',
                'faulty',
                'retired',
                'technician_assigned',
                'technician_returned',
                'installed'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_technician_assignments');

        if (Schema::hasTable('inventory_transactions')) {
            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN action ENUM(
                'added',
                'assigned',
                'returned',
                'replaced',
                'faulty',
                'retired'
            ) NOT NULL");
        }

        if (Schema::hasTable('users')) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'isp_admin', 'isp_staff') NOT NULL DEFAULT 'isp_staff'");
        }
    }
};
