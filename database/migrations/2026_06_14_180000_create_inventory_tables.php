<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_purchases')) {
            Schema::create('inventory_purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->string('vendor_name');
                $table->string('invoice_number')->nullable();
                $table->decimal('total_cost', 12, 2)->default(0);
                $table->date('purchase_date');
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['isp_id', 'purchase_date']);
            });
        }

        if (! Schema::hasTable('inventory_category_thresholds')) {
            Schema::create('inventory_category_thresholds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->string('category', 32);
                $table->unsignedInteger('low_stock_threshold')->default(5);
                $table->timestamps();

                $table->unique(['isp_id', 'category']);
            });
        }

        if (! Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->foreignId('inventory_purchase_id')->nullable()->constrained('inventory_purchases')->nullOnDelete();
                $table->string('name');
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->string('serial_number');
                $table->string('mac_address')->nullable();
                $table->enum('category', ['router', 'ap', 'cpe', 'switch', 'radio']);
                $table->enum('status', ['available', 'assigned', 'faulty', 'retired'])->default('available');
                $table->decimal('purchase_price', 12, 2)->nullable();
                $table->date('purchase_date')->nullable();
                $table->date('warranty_expiry')->nullable();
                $table->enum('location', ['warehouse', 'pop_site', 'customer', 'technician'])->default('warehouse');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['isp_id', 'serial_number']);
                $table->index(['isp_id', 'status']);
                $table->index(['isp_id', 'category', 'status']);
            });
        }

        if (! Schema::hasTable('inventory_assignments')) {
            Schema::create('inventory_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
                $table->foreignId('technician_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('installation_date')->nullable();
                $table->string('installation_location')->nullable();
                $table->string('installation_gps')->nullable();
                $table->enum('installation_status', ['pending', 'completed'])->default('pending');
                $table->timestamp('assigned_at');
                $table->timestamp('returned_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('replaced_by_assignment_id')->nullable()->constrained('inventory_assignments')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['isp_id', 'is_active']);
                $table->index(['inventory_item_id', 'is_active']);
                $table->index(['customer_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('inventory_transactions')) {
            Schema::create('inventory_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->enum('action', ['added', 'assigned', 'returned', 'replaced', 'faulty', 'retired']);
                $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('inventory_assignment_id')->nullable()->constrained('inventory_assignments')->nullOnDelete();
                $table->foreignId('related_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['isp_id', 'created_at']);
                $table->index(['inventory_item_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_assignments');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_category_thresholds');
        Schema::dropIfExists('inventory_purchases');
    }
};
