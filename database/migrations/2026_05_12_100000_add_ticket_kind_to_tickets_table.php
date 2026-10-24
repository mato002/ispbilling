<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'ticket_kind')) {
                $table->string('ticket_kind', 32)->default('customer')->after('channel');
            }
        });

        $hasIndex = collect(Schema::getIndexes('tickets'))
            ->contains(fn (array $index): bool => ($index['columns'] ?? []) === ['ticket_kind', 'isp_id', 'status']);
        if (! $hasIndex) {
            Schema::table('tickets', fn (Blueprint $table) => $table->index(['ticket_kind', 'isp_id', 'status']));
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['ticket_kind', 'isp_id', 'status']);
            $table->dropColumn('ticket_kind');
        });
    }
};
