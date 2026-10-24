<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Production migrations may have been interrupted after MySQL applied
        // some DDL but before Laravel recorded this migration. Add only the
        // missing columns so a rerun safely continues from the existing schema.
        Schema::table('routers', function (Blueprint $table) {
            // Asset / CMDB
            if (! Schema::hasColumn('routers', 'vendor')) $table->string('vendor')->nullable();
            if (! Schema::hasColumn('routers', 'model')) $table->string('model')->nullable();
            if (! Schema::hasColumn('routers', 'serial_number')) $table->string('serial_number')->nullable();
            if (! Schema::hasColumn('routers', 'mac_address')) $table->string('mac_address')->nullable();
            if (! Schema::hasColumn('routers', 'latitude')) $table->decimal('latitude', 10, 7)->nullable();
            if (! Schema::hasColumn('routers', 'longitude')) $table->decimal('longitude', 10, 7)->nullable();
            if (! Schema::hasColumn('routers', 'branch')) $table->string('branch')->nullable();
            if (! Schema::hasColumn('routers', 'tags')) $table->string('tags')->nullable(); // comma-separated
            if (! Schema::hasColumn('routers', 'owner_user_id')) $table->foreignId('owner_user_id')->nullable();
            if (! Schema::hasColumn('routers', 'lifecycle_status')) $table->string('lifecycle_status')->default('live'); // stock|live|maintenance|retired
            if (! Schema::hasColumn('routers', 'purchase_date')) $table->date('purchase_date')->nullable();
            if (! Schema::hasColumn('routers', 'warranty_expiry')) $table->date('warranty_expiry')->nullable();
            if (! Schema::hasColumn('routers', 'monthly_cost')) $table->decimal('monthly_cost', 12, 2)->nullable();

            // Cached telemetry (last successful poll) — read by the NOC view
            if (! Schema::hasColumn('routers', 'reachability_status')) $table->string('reachability_status')->default('unknown'); // online|degraded|offline|unknown|rebooting
            if (! Schema::hasColumn('routers', 'last_seen_at')) $table->timestamp('last_seen_at')->nullable();
            if (! Schema::hasColumn('routers', 'last_telemetry_at')) $table->timestamp('last_telemetry_at')->nullable();
            if (! Schema::hasColumn('routers', 'last_latency_ms')) $table->unsignedInteger('last_latency_ms')->nullable();
            if (! Schema::hasColumn('routers', 'last_health_pct')) $table->unsignedTinyInteger('last_health_pct')->nullable();
            if (! Schema::hasColumn('routers', 'last_cpu_pct')) $table->unsignedTinyInteger('last_cpu_pct')->nullable();
            if (! Schema::hasColumn('routers', 'last_mem_pct')) $table->unsignedTinyInteger('last_mem_pct')->nullable();
            if (! Schema::hasColumn('routers', 'last_uptime_seconds')) $table->unsignedBigInteger('last_uptime_seconds')->nullable();
            if (! Schema::hasColumn('routers', 'last_firmware')) $table->string('last_firmware')->nullable();
            if (! Schema::hasColumn('routers', 'last_board')) $table->string('last_board')->nullable();
            if (! Schema::hasColumn('routers', 'last_active_hotspot_users')) $table->unsignedInteger('last_active_hotspot_users')->nullable();
            if (! Schema::hasColumn('routers', 'last_active_pppoe_users')) $table->unsignedInteger('last_active_pppoe_users')->nullable();
            if (! Schema::hasColumn('routers', 'last_interfaces_up')) $table->unsignedSmallInteger('last_interfaces_up')->nullable();
            if (! Schema::hasColumn('routers', 'last_interfaces_total')) $table->unsignedSmallInteger('last_interfaces_total')->nullable();
            if (! Schema::hasColumn('routers', 'last_poll_error')) $table->string('last_poll_error')->nullable();
        });

        // Separate index and FK creation from the column batch and guard them
        // as well: MySQL DDL is not transactional on the production server.
        $indexes = collect(Schema::getIndexes('routers'));
        foreach (['reachability_status', 'branch', 'lifecycle_status'] as $column) {
            $exists = $indexes->contains(fn (array $index): bool => ($index['columns'] ?? []) === [$column]);
            if (! $exists) {
                Schema::table('routers', fn (Blueprint $table) => $table->index($column));
                $indexes = collect(Schema::getIndexes('routers'));
            }
        }

        $foreignKeyExists = collect(Schema::getForeignKeys('routers'))
            ->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['owner_user_id']);
        if (Schema::hasColumn('routers', 'owner_user_id') && ! $foreignKeyExists) {
            Schema::table('routers', fn (Blueprint $table) => $table->foreign('owner_user_id')->references('id')->on('users')->nullOnDelete());
        }
    }

    public function down(): void
    {
        $foreignKeyExists = collect(Schema::getForeignKeys('routers'))
            ->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['owner_user_id']);
        if ($foreignKeyExists) {
            Schema::table('routers', fn (Blueprint $table) => $table->dropForeign(['owner_user_id']));
        }

        $indexes = collect(Schema::getIndexes('routers'));
        foreach (['reachability_status', 'branch', 'lifecycle_status'] as $column) {
            if ($indexes->contains(fn (array $index): bool => ($index['columns'] ?? []) === [$column])) {
                Schema::table('routers', fn (Blueprint $table) => $table->dropIndex([$column]));
                $indexes = collect(Schema::getIndexes('routers'));
            }
        }

        Schema::table('routers', function (Blueprint $table) {
            $columns = [
                'vendor', 'model', 'serial_number', 'mac_address', 'latitude', 'longitude',
                'branch', 'tags', 'owner_user_id', 'lifecycle_status', 'purchase_date',
                'warranty_expiry', 'monthly_cost', 'reachability_status', 'last_seen_at',
                'last_telemetry_at', 'last_latency_ms', 'last_health_pct', 'last_cpu_pct',
                'last_mem_pct', 'last_uptime_seconds', 'last_firmware', 'last_board',
                'last_active_hotspot_users', 'last_active_pppoe_users', 'last_interfaces_up',
                'last_interfaces_total', 'last_poll_error',
            ];
            $existing = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('routers', $column)));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
