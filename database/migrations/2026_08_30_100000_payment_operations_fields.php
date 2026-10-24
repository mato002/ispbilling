<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'posting_status')) $table->string('posting_status', 32)->default('posted');
            if (! Schema::hasColumn('payments', 'assigned_by')) $table->foreignId('assigned_by')->nullable();
            if (! Schema::hasColumn('payments', 'assigned_at')) $table->timestamp('assigned_at')->nullable();
        });

        $hasForeignKey = collect(Schema::getForeignKeys('payments'))
            ->contains(fn (array $key): bool => ($key['columns'] ?? []) === ['assigned_by']);
        if (Schema::hasColumn('payments', 'assigned_by') && ! $hasForeignKey) {
            Schema::table('payments', fn (Blueprint $table) => $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete());
        }

        $hasIndex = collect(Schema::getIndexes('payments'))
            ->contains(fn (array $index): bool => ($index['columns'] ?? []) === ['isp_id', 'posting_status']);
        if (Schema::hasColumn('payments', 'posting_status') && ! $hasIndex) {
            Schema::table('payments', fn (Blueprint $table) => $table->index(['isp_id', 'posting_status']));
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'account_credit')) {
                $table->decimal('account_credit', 12, 2)->default(0);
            }
        });

        DB::table('payments')
            ->where('status', 'completed')
            ->whereNull('customer_id')
            ->update(['posting_status' => 'unposted']);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('account_credit');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn(['posting_status', 'assigned_at']);
        });
    }
};
