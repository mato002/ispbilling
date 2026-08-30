<?php

namespace App\Support\Search;

class SearchHelper
{
    public static function likePattern(?string $term): ?string
    {
        $term = trim((string) $term);

        if ($term === '') {
            return null;
        }

        return '%'.addcslashes($term, '%_\\').'%';
    }

    /**
     * @param  list<string>  $columns
     */
    public static function applyLike(\Illuminate\Database\Eloquent\Builder $query, ?string $term, array $columns, string $boolean = 'or'): void
    {
        $pattern = self::likePattern($term);

        if ($pattern === null || $columns === []) {
            return;
        }

        $query->where(function ($inner) use ($columns, $pattern, $boolean) {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', $pattern);
            }
        });
    }
}
