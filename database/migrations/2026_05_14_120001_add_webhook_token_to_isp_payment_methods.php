<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('isp_payment_methods')) {
            return;
        }

        if (! Schema::hasColumn('isp_payment_methods', 'webhook_token')) {
            Schema::table('isp_payment_methods', function (Blueprint $table) {
                $table->string('webhook_token', 64)->nullable()->unique()->after('callback_url');
            });
        }

        DB::table('isp_payment_methods')
            ->whereNull('webhook_token')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('isp_payment_methods')
                        ->where('id', $row->id)
                        ->update(['webhook_token' => Str::random(48)]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('isp_payment_methods', 'webhook_token')) {
            Schema::table('isp_payment_methods', function (Blueprint $table) {
                $table->dropColumn('webhook_token');
            });
        }
    }
};
