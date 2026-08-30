<?php

namespace App\Support\Search;

use Illuminate\Http\Request;

class ActiveFilterChips
{
    /**
     * Build removable filter chips from the current request.
     *
     * @param  array<string, string|array{label: string, values?: array<string, string>}>  $definitions
     * @return list<array{key: string, label: string, url: string}>
     */
    public static function build(Request $request, string $baseUrl, array $definitions): array
    {
        $chips = [];

        foreach ($definitions as $key => $meta) {
            $value = $request->query($key);

            if ($value === null || $value === '') {
                continue;
            }

            $label = is_array($meta) ? ($meta['label'] ?? $key) : $meta;
            $display = is_array($meta) && isset($meta['values'][$value])
                ? $meta['values'][$value]
                : (string) $value;

            $query = $request->query();
            unset($query[$key], $query['page']);

            $chips[] = [
                'key' => $key,
                'label' => "{$label}: {$display}",
                'url' => $query === []
                    ? $baseUrl
                    : $baseUrl.'?'.http_build_query($query),
            ];
        }

        return $chips;
    }
}
