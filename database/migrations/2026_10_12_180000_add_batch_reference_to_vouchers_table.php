<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vouchers', 'batch_reference')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->string('batch_reference', 40)->nullable()->after('voucher_code');
                $table->index(['isp_id', 'batch_reference']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vouchers', 'batch_reference')) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->dropIndex(['isp_id', 'batch_reference']);
                $table->dropColumn('batch_reference');
            });
        }
    }
};
