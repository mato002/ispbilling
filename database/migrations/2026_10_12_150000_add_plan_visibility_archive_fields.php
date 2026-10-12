<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plans', 'customer_visible')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->boolean('customer_visible')->default(true);
            });
        }

        if (! Schema::hasColumn('plans', 'archived_at')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'archived_at')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }

        if (Schema::hasColumn('plans', 'customer_visible')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropColumn('customer_visible');
            });
        }
    }
};
