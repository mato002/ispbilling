<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('ticket_kind', 32)->default('customer')->after('channel');
            $table->index(['ticket_kind', 'isp_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['ticket_kind', 'isp_id', 'status']);
            $table->dropColumn('ticket_kind');
        });
    }
};
