<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('router_migration_batches')) {
            Schema::create('router_migration_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('isp_id')->constrained('i_s_p_s')->cascadeOnDelete();
                $table->foreignId('old_router_id')->constrained('routers')->cascadeOnDelete();
                $table->foreignId('new_router_id')->constrained('routers')->cascadeOnDelete();
                $table->string('status', 32)->default('pending');
                $table->unsignedInteger('total_customers')->default(0);
                $table->unsignedInteger('processed_customers')->default(0);
                $table->unsignedInteger('success_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['isp_id', 'status']);
            });
        }

        if (! Schema::hasTable('router_migration_logs')) {
            Schema::create('router_migration_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('batch_id')->constrained('router_migration_batches')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->string('action', 64);
                $table->string('status', 32);
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['batch_id', 'customer_id', 'action']);
                $table->index(['batch_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('router_migration_logs');
        Schema::dropIfExists('router_migration_batches');
    }
};
