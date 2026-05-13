<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            if (! Schema::hasColumn('routers', 'poll_failure_count')) {
                $table->unsignedSmallInteger('poll_failure_count')->default(0)->after('last_poll_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            if (Schema::hasColumn('routers', 'poll_failure_count')) {
                $table->dropColumn('poll_failure_count');
            }
        });
    }
};
