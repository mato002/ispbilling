<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Search\ActiveFilterChips;
use Illuminate\Http\Request;

trait InteractsWithListings
{
    /** @return list<int> */
    protected function perPageOptions(): array
    {
        return [10, 15, 25, 50];
    }

    protected function resolvePerPage(Request $request, int $default = 15): int
    {
        $perPage = (int) $request->input('per_page', $default);

        return in_array($perPage, $this->perPageOptions(), true) ? $perPage : $default;
    }

    /**
     * @param  array<string, string|array{label: string, values?: array<string, string>}>  $definitions
     * @return list<array{key: string, label: string, url: string}>
     */
    protected function listingFilterChips(Request $request, string $indexRoute, array $definitions): array
    {
        $definitions['per_page'] = [
            'label' => 'Per page',
            'values' => array_combine(
                array_map('strval', $this->perPageOptions()),
                array_map('strval', $this->perPageOptions()),
            ),
        ];

        return ActiveFilterChips::build($request, $indexRoute, $definitions);
    }

    /**
     * @return list<array{label: string, url: string, download?: bool, title?: string}>
     */
    protected function listingExportFormats(Request $request, string $exportRoute, string $label = 'Export CSV'): array
    {
        $query = http_build_query($request->except('page'));

        return [[
            'label' => $label,
            'url' => $exportRoute.($query !== '' ? '?'.$query : ''),
            'download' => true,
            'title' => $label.' (respects current filters)',
        ]];
    }

    protected function listingSort(Request $request, string $default = 'latest'): string
    {
        return (string) $request->input('sort', $default);
    }
}
