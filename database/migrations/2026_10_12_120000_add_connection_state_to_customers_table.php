<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addConnectionStatus = ! Schema::hasColumn('customers', 'connection_status');
        $addDisconnectedAt = ! Schema::hasColumn('customers', 'disconnected_at');

        if (! $addConnectionStatus && ! $addDisconnectedAt) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) use ($addConnectionStatus, $addDisconnectedAt) {
            if ($addConnectionStatus) {
                $table->string('connection_status', 32)->default('disconnected');
                $table->index(['isp_id', 'connection_status'], 'customers_isp_connection_status_index');
            }

            if ($addDisconnectedAt) {
                $table->timestamp('disconnected_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        $hasConnectionStatus = Schema::hasColumn('customers', 'connection_status');
        $hasDisconnectedAt = Schema::hasColumn('customers', 'disconnected_at');

        if (! $hasConnectionStatus && ! $hasDisconnectedAt) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) use ($hasConnectionStatus, $hasDisconnectedAt) {
            if ($hasConnectionStatus) {
                $table->dropIndex('customers_isp_connection_status_index');
                $table->dropColumn('connection_status');
            }

            if ($hasDisconnectedAt) {
                $table->dropColumn('disconnected_at');
            }
        });
    }
};
