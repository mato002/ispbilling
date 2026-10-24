<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('i_s_p_s') && ! Schema::hasColumn('i_s_p_s', 'saas_tier')) {
            Schema::table('i_s_p_s', function (Blueprint $table) {
                $table->string('saas_tier', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        // This compatibility repair only fills in a missing field. Keep it on rollback.
    }
};
