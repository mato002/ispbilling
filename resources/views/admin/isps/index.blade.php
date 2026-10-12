<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('All ISPs') }}
            </h2>
            <x-form-modal-link :href="route('admin.isps.create')" class="px-4 py-2">
                Add ISP
            </x-form-modal-link>
        </div>
    </x-slot>

    <x-resource-index
        :filter-action="route('admin.isps.index')"
        :filter-chips="$filterChips ?? []"
        :export-formats="$exportFormats ?? []"
        :paginator="$isps"
        :filter-auto-search="false"
        :empty="$isps->isEmpty()"
        empty-title="No ISPs found"
        empty-message="Create an ISP or adjust your search filters."
        :empty-cta-href="route('admin.isps.create')"
        empty-cta-label="Add ISP"
    >
        <x-slot:filters>
            <x-listing-filters.search
                name="local_search"
                placeholder="Name, company, email, phone…"
                data-pb-live-filter
            />
            <x-listing-filters.select
                name="is_active"
                label="Status"
                placeholder="All status"
                :options="['1' => 'Active', '0' => 'Inactive']"
            />
            <x-listing-filters.select
                name="saas_status"
                label="SaaS billing"
                placeholder="All SaaS states"
                class="min-w-[150px]"
                :options="[
                    'none' => 'None',
                    'active' => 'Active',
                    'trialing' => 'Trialing',
                    'past_due' => 'Past due',
                    'cancelled' => 'Cancelled',
                    'suspended' => 'Suspended',
                ]"
            />
            <x-listing-filters.select
                name="onboarding"
                label="Onboarding"
                placeholder="All onboarding"
                class="min-w-[150px]"
                :options="[
                    'complete' => 'Complete',
                    'pending' => 'Pending',
                ]"
            />
            <x-listing-filters.date name="created_from" label="Created from" />
            <x-listing-filters.date name="created_to" label="Created to" />
            <x-listing-filters.select
                name="sort"
                label="Sort by"
                class="min-w-[150px]"
                :value="request('sort', 'latest')"
                :options="[
                    'latest' => 'Newest first',
                    'oldest' => 'Oldest first',
                    'name_az' => 'Name A–Z',
                    'name_za' => 'Name Z–A',
                    'customers_desc' => 'Most customers',
                ]"
            />
            <x-listing-filters.per-page />
        </x-slot:filters>

        <x-slot:table>
            <x-listing-table>
                <thead>
                    <tr>
                        <th>ISP Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Customers</th>
                        <th>Routers</th>
                        <th>Plans</th>
                        <th>Status</th>
                        <th>SaaS</th>
                        <th>Created</th>
                        <x-table-actions.header />
                    </tr>
                </thead>
                <tbody>
                    @include('admin.isps.partials.rows')
                </tbody>
            </x-listing-table>
            <script>
            (() => {
                if (window.__pbIspLocalSearchInstalled) return;
                window.__pbIspLocalSearchInstalled = true;

                const applySearch = (input) => {
                    const listing = input.closest('[data-listing-root]');
                    if (!listing) return;

                    const tokens = input.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
                    const rows = [...listing.querySelectorAll('tbody tr[data-search]')];
                    let visibleCount = 0;
                    rows.forEach((row) => {
                        const haystack = (row.dataset.search || '').toLowerCase();
                        row.hidden = !tokens.every((token) => haystack.includes(token));
                        if (!row.hidden) visibleCount += 1;
                    });

                    let emptyRow = listing.querySelector('[data-local-search-empty]');
                    if (tokens.length > 0 && rows.length > 0 && visibleCount === 0) {
                        if (!emptyRow) {
                            emptyRow = document.createElement('tr');
                            emptyRow.dataset.localSearchEmpty = '';
                            emptyRow.innerHTML = '<td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500">No loaded rows match your search.</td>';
                            listing.querySelector('tbody')?.append(emptyRow);
                        }
                    } else {
                        emptyRow?.remove();
                    }
                };

                document.addEventListener('input', (event) => {
                    const input = event.target.closest?.('[data-pb-live-filter]');
                    if (input) applySearch(input);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' && event.target.matches?.('[data-pb-live-filter]')) {
                        event.preventDefault();
                    }
                });

                const applyExistingSearch = (root = document) => {
                    root.querySelectorAll?.('[data-pb-live-filter]').forEach(applySearch);
                };

                document.addEventListener('DOMContentLoaded', () => applyExistingSearch());
                document.addEventListener('turbo:frame-render', (event) => applyExistingSearch(event.target));
                applyExistingSearch();
            })();
            </script>
        </x-slot:table>
    </x-resource-index>
</x-admin-layout>
