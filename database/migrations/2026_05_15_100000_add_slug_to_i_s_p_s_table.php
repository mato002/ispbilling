<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('i_s_p_s', function (Blueprint $table) {
            if (! Schema::hasColumn('i_s_p_s', 'slug')) {
                $table->string('slug', 64)->nullable()->after('name');
            }
        });

        $hasUnique = collect(Schema::getIndexes('i_s_p_s'))
            ->contains(fn (array $index): bool => ($index['columns'] ?? []) === ['slug'] && ($index['unique'] ?? false));
        if (! $hasUnique) {
            Schema::table('i_s_p_s', fn (Blueprint $table) => $table->unique('slug'));
        }

        $rows = \Illuminate\Support\Facades\DB::table('i_s_p_s')->select('id', 'name')->orderBy('id')->get();
        foreach ($rows as $row) {
            $base = \Illuminate\Support\Str::slug($row->name ?: 'isp-'.$row->id);
            if ($base === '') {
                $base = 'isp-'.$row->id;
            }
            $slug = $base;
            $i = 0;
            while (\Illuminate\Support\Facades\DB::table('i_s_p_s')->where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                $i++;
                $slug = $base.'-'.$i;
            }
            \Illuminate\Support\Facades\DB::table('i_s_p_s')->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('i_s_p_s', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
