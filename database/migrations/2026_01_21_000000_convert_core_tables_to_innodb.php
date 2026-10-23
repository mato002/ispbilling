<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert any tables left behind by a partial first deploy before later
     * migrations add foreign keys that require InnoDB.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (['i_s_p_s', 'routers', 'users', 'migrations'] as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
            }
        }
    }

    /**
     * Keep core metadata, user, and tenant tables on InnoDB when rolling back
     * later migrations.
     */
    public function down(): void
    {
        // Intentionally forward-only.
    }
};
