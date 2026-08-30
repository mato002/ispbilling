<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Global promo codes') }}</h2>
            <a href="{{ route('admin.promo-codes.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Create</a>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <x-resource-index
        :filter-action="route('admin.promo-codes.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$codes"
        :empty="$codes->isEmpty()"
        empty-title="No promo codes"
        empty-message="Create a global promo code for marketing campaigns."
        empty-cta-label="Create code"
        :empty-cta-href="route('admin.promo-codes.create')"
        :show-flash="false"
    >
        <x-slot:filters>
            <x-listing-filters.search
                placeholder="Code or title…"
            />
            <x-listing-filters.select
                name="kind"
                label="Kind"
                placeholder="All kinds"
                class="min-w-[160px]"
                :options="[
                    \App\Models\GlobalPromoCode::KIND_FIRST_MONTH_FREE => 'First month free',
                    \App\Models\GlobalPromoCode::KIND_REFERRAL => 'Referral',
                    \App\Models\GlobalPromoCode::KIND_PERCENT_OFF => 'Percent off',
                ]"
            />
            <x-listing-filters.select
                name="is_active"
                label="Active"
                placeholder="All"
                :options="['1' => 'Yes', '0' => 'No']"
            />
            <x-listing-filters.per-page :default="20" />
        </x-slot:filters>

        <x-slot:table>
            <x-listing-table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Kind</th>
                        <th>Active</th>
                        <th>Redemptions</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @foreach ($codes as $row)
                        <tr>
                            <td class="font-mono font-semibold">{{ $row->code }}</td>
                            <td>{{ $row->kind }}</td>
                            <td>{{ $row->is_active ? 'Yes' : 'No' }}</td>
                            <td>{{ $row->redemption_count }}@if ($row->max_redemptions) / {{ $row->max_redemptions }} @endif</td>
                            <td class="whitespace-nowrap text-right">
                                <x-table-actions>
                                    <x-table-actions.link :href="route('admin.promo-codes.edit', $row)" label="Edit" />
                                    <x-table-actions.form
                                        :action="route('admin.promo-codes.destroy', $row)"
                                        method="DELETE"
                                        label="Delete"
                                        onsubmit="return confirm('Delete this code?');"
                                    />
                                </x-table-actions>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-listing-table>
        </x-slot:table>
    </x-resource-index>
</x-admin-layout>
