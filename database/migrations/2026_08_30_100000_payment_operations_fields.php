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
            $table->string('posting_status', 32)->default('posted')->after('status');
            $table->foreignId('assigned_by')->nullable()->after('posting_status')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            $table->index(['isp_id', 'posting_status']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('account_credit', 12, 2)->default(0)->after('status');
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
