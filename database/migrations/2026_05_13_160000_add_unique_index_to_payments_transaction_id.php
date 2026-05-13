<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Multiple NULL transaction_id values remain allowed under MySQL UNIQUE.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $dupes = DB::table('payments')
                ->select('transaction_id')
                ->whereNotNull('transaction_id')
                ->where('transaction_id', '!=', '')
                ->groupBy('transaction_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('transaction_id');

            foreach ($dupes as $transactionId) {
                $ids = DB::table('payments')
                    ->where('transaction_id', $transactionId)
                    ->orderBy('id')
                    ->pluck('id');

                foreach ($ids->slice(1) as $id) {
                    DB::table('payments')
                        ->where('id', $id)
                        ->update([
                            'transaction_id' => $transactionId.'-legacy-dup-'.$id,
                        ]);
                }
            }
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unique('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['transaction_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('transaction_id');
        });
    }
};
