<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('end_date');
            }
        });

        $indexed = collect(Schema::getIndexes('subscriptions'))
            ->contains(fn (array $index): bool => ($index['columns'] ?? []) === ['ends_at']);
        if (Schema::hasColumn('subscriptions', 'ends_at') && ! $indexed) {
            Schema::table('subscriptions', fn (Blueprint $table) => $table->index('ends_at'));
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('ends_at');
        });
    }
};
