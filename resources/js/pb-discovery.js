const DISCOVERY_RECENT_KEY = 'pb.discovery.recent';
const DISCOVERY_FAVORITES_KEY = 'pb.discovery.favorites';

function discoverySearchUrl() {
    return window.__pbDiscovery?.searchUrl ?? null;
}

function discoveryNormalizeStored(entries) {
    if (! Array.isArray(entries)) {
        return [];
    }

    return entries.filter((entry) => entry && entry.id && entry.url);
}

async function discoveryFetchResults(query, limit = 24) {
    const searchUrl = discoverySearchUrl();

    if (! searchUrl || ! String(query ?? '').trim()) {
        return [];
    }

    const params = new URLSearchParams({ q: String(query).trim() });

    if (limit !== 24) {
        params.set('limit', String(limit));
    }

    const response = await fetch(`${searchUrl}?${params.toString()}`, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        return [];
    }

    const payload = await response.json();

    return Array.isArray(payload.results) ? payload.results : [];
}

function discoveryCategoryLabel(category) {
    return window.__pbDiscovery?.categoryLabels?.[category] ?? category;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('pbSearchableSelect', (config = {}) => ({
        options: Array.isArray(config.options) ? config.options : [],
        selected: String(config.selected ?? ''),
        query: '', open: false, highlighted: 0,
        get filteredOptions() {
            const search = this.query.trim().toLowerCase();
            return this.options.filter(option => !search || option.label.toLowerCase().includes(search));
        },
        init() { this.query = this.options.find(option => option.value === this.selected)?.label ?? ''; },
        search() {
            this.open = true;
            const selectedLabel = this.options.find(option => option.value === this.selected)?.label ?? '';
            if (this.query !== selectedLabel) this.selected = '';
            this.highlighted = 0;
        },
        select(value, label) {
            this.selected = String(value); this.query = label; this.open = false;
            this.$el.querySelector('input[type="hidden"]').dispatchEvent(new Event('pb-filter-change', { bubbles: true }));
            this.$el.querySelector('input[type="hidden"]').dispatchEvent(new Event('change', { bubbles: true }));
        },
        highlightNext() { this.open = true; this.highlighted = Math.min(this.highlighted + 1, this.filteredOptions.length - 1); },
        highlightPrevious() { this.highlighted = Math.max(this.highlighted - 1, 0); },
        chooseHighlighted() { const option = this.filteredOptions[this.highlighted]; if (option) this.select(option.value, option.label); },
    }));

    Alpine.data('pbTopbar', () => ({
        searchOpen: false,
        quickActionsOpen: false,
        profileOpen: false,
        theme: (localStorage.getItem('pb.theme') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')),
        paletteQuery: '',
        paletteHighlightIndex: 0,
        paletteResults: [],
        paletteLoading: false,
        paletteSearchTimer: null,
        discoveryRecent: discoveryNormalizeStored(JSON.parse(localStorage.getItem(DISCOVERY_RECENT_KEY) || '[]')),
        discoveryFavorites: discoveryNormalizeStored(JSON.parse(localStorage.getItem(DISCOVERY_FAVORITES_KEY) || '[]')),

        init() {
            this.$el.addEventListener('pb-filter-change', () => this.submitForm());

            this.$watch('discoveryRecent', (value) => {
                localStorage.setItem(DISCOVERY_RECENT_KEY, JSON.stringify(value.slice(0, window.__pbDiscovery?.recentLimit ?? 12)));
            });

            this.$watch('discoveryFavorites', (value) => {
                localStorage.setItem(DISCOVERY_FAVORITES_KEY, JSON.stringify(value.slice(0, window.__pbDiscovery?.favoritesLimit ?? 12)));
            });

            this.$watch('paletteQuery', () => {
                this.paletteHighlightIndex = 0;
                this.schedulePaletteSearch();
            });

            this.$watch('searchOpen', (open) => {
                if (open) {
                    this.paletteHighlightIndex = 0;
                    window.setTimeout(() => this.$refs.searchInput?.focus(), 30);
                } else {
                    this.paletteQuery = '';
                    this.paletteResults = [];
                    this.paletteLoading = false;
                }
            });
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('pb.theme', this.theme);

            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        closePalette() {
            this.searchOpen = false;
        },

        schedulePaletteSearch() {
            clearTimeout(this.paletteSearchTimer);

            if (! this.paletteQuery.trim()) {
                this.paletteResults = [];
                this.paletteLoading = false;

                return;
            }

            this.paletteSearchTimer = setTimeout(() => this.runPaletteSearch(), 150);
        },

        async runPaletteSearch() {
            const query = this.paletteQuery.trim();

            if (! query) {
                this.paletteResults = [];
                this.paletteLoading = false;

                return;
            }

            this.paletteLoading = true;

            try {
                this.paletteResults = await discoveryFetchResults(query, window.__pbDiscovery?.resultsLimit ?? 24);
            } catch {
                this.paletteResults = [];
            } finally {
                this.paletteLoading = false;
            }
        },

        get paletteFlatResults() {
            if (this.paletteQuery.trim() !== '') {
                return this.paletteResults;
            }

            return [...this.discoveryRecent, ...this.discoveryFavorites.filter((fav) => ! this.discoveryRecent.some((r) => r.id === fav.id))];
        },

        movePaletteSelection(step) {
            const total = this.paletteFlatResults.length;

            if (total === 0) {
                return;
            }

            this.paletteHighlightIndex = (this.paletteHighlightIndex + step + total) % total;
        },

        openPaletteSelection() {
            const item = this.paletteFlatResults[this.paletteHighlightIndex];

            if (item) {
                this.navigatePaletteItem(item);
            }
        },

        navigatePaletteItem(item) {
            if (! item?.url) {
                return;
            }

            this.rememberDiscoveryItem(item);
            this.closePalette();

            if (window.Turbo) {
                window.Turbo.visit(item.url);
            } else {
                window.location.href = item.url;
            }
        },

        rememberDiscoveryItem(item) {
            this.discoveryRecent = [
                item,
                ...this.discoveryRecent.filter((entry) => entry.id !== item.id),
            ].slice(0, window.__pbDiscovery?.recentLimit ?? 12);
        },

        toggleDiscoveryFavorite(id) {
            const item = this.paletteFlatResults.find((entry) => entry.id === id)
                ?? this.discoveryRecent.find((entry) => entry.id === id)
                ?? this.discoveryFavorites.find((entry) => entry.id === id);

            if (! item) {
                return;
            }

            if (this.discoveryFavorites.some((entry) => entry.id === id)) {
                this.discoveryFavorites = this.discoveryFavorites.filter((entry) => entry.id !== id);
            } else {
                this.discoveryFavorites = [item, ...this.discoveryFavorites].slice(0, window.__pbDiscovery?.favoritesLimit ?? 12);
            }
        },

        isDiscoveryFavorite(id) {
            return this.discoveryFavorites.some((entry) => entry.id === id);
        },

        groupedPaletteResults() {
            if (this.paletteQuery.trim() === '') {
                return [];
            }

            const groups = {};

            this.paletteResults.forEach((item) => {
                const key = item.category || 'navigation';

                if (! groups[key]) {
                    groups[key] = [];
                }

                groups[key].push(item);
            });

            return Object.entries(groups).map(([key, items]) => ({
                key,
                label: discoveryCategoryLabel(key),
                items,
            }));
        },

        paletteItemIndex(sectionKey, index) {
            let offset = 0;

            for (const section of this.groupedPaletteResults()) {
                if (section.key === sectionKey) {
                    return offset + index;
                }

                offset += section.items.length;
            }

            return index;
        },
    }));

    Alpine.data('pbFilterForm', (options = {}) => ({
        autoSearch: Boolean(options.autoSearch ?? true),
        debounceMs: Number(options.debounceMs ?? 350),
        timer: null,
        controller: null,

        init() {
            if (! this.autoSearch) {
                return;
            }

            this.$el.addEventListener('submit', (event) => {
                event.preventDefault();
                this.submitForm();
            });

            this.$el.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([data-filter-search-only])').forEach((field) => {
                if (field.type === 'text' || field.type === 'search' || field.type === 'email' || field.type === 'tel') {
                    field.addEventListener('input', () => this.scheduleSubmit());

                    return;
                }

                field.addEventListener('change', () => this.submitForm());
            });

            this.$el.querySelectorAll('select').forEach((field) => {
                field.addEventListener('change', () => this.submitForm());
            });
        },

        scheduleSubmit() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.submitForm(), this.debounceMs);
        },

        submitForm() {
            this.updateListing();
        },

        async updateListing() {
            const form = this.$el;
            const listing = form.closest('[data-listing-root]') ?? document.querySelector('#pb-main');
            const currentBody = listing?.querySelector('table tbody');

            if (!currentBody || form.method.toUpperCase() !== 'GET') {
                form.submit();
                return;
            }

            this.controller?.abort();
            this.controller = new AbortController();
            const params = new URLSearchParams(new FormData(form));
            params.delete('page');
            const url = new URL(form.action, window.location.origin);
            url.search = params.toString();

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'text/html',
                        'X-Listing-Refresh': '1',
                    },
                    credentials: 'same-origin',
                    signal: this.controller.signal,
                });
                if (!response.ok) throw new Error(`Listing search failed (${response.status})`);

                const contentType = response.headers.get('content-type') ?? '';
                if (contentType.includes('application/json')) {
                    const data = await response.json();
                    currentBody.innerHTML = data.rows;
                    listing.querySelector('[data-listing-pagination]')?.replaceChildren(document.createRange().createContextualFragment(data.pagination));
                    this.updateListingCount(listing, data);
                } else {
                    const sourceDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const sourceListing = sourceDocument.querySelector('[data-listing-root]') ?? sourceDocument.querySelector('#pb-main');
                    const sourceBody = sourceListing?.querySelector('table tbody');
                    if (!sourceBody) {
                        const columns = currentBody.closest('table')?.querySelectorAll('thead th').length ?? 1;
                        currentBody.innerHTML = `<tr><td colspan="${columns}" class="px-4 py-10 text-center text-sm text-slate-500">No records match these filters.</td></tr>`;
                        listing.querySelector('[data-listing-pagination]')?.replaceChildren();
                        this.updateListingCount(listing, { from: 0, to: 0, total: 0 });
                        window.history.replaceState({}, '', url);
                        return;
                    }

                    currentBody.innerHTML = sourceBody.innerHTML;
                    const currentPagination = listing.querySelector('[data-listing-pagination]');
                    const sourcePagination = sourceListing.querySelector('[data-listing-pagination]');
                    if (currentPagination && sourcePagination) currentPagination.innerHTML = sourcePagination.innerHTML;
                    ['from', 'to', 'total'].forEach((key) => {
                        const current = listing.querySelector(`[data-listing-${key}]`);
                        const source = sourceListing.querySelector(`[data-listing-${key}]`);
                        if (current && source) current.textContent = source.textContent;
                    });
                }

                window.history.replaceState({}, '', url);
            } catch (error) {
                if (error.name !== 'AbortError') console.error(error);
            }
        },

        updateListingCount(listing, data) {
            const values = { from: data.from, to: data.to, total: Number(data.total).toLocaleString() };
            Object.entries(values).forEach(([key, value]) => {
                const target = listing.querySelector(`[data-listing-${key}]`);
                if (target) target.textContent = value;
            });
        },
    }));

    Alpine.data('pbDataTable', (config = {}) => ({
        query: '',
        activeChip: config.defaultChip ?? 'all',
        debounceMs: Number(config.debounceMs ?? 250),
        debounceTimer: null,

        init() {
            this.$watch('query', () => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {}, this.debounceMs);
            });
        },

        rowVisible(searchBlob, chipValue = null) {
            const tokens = String(this.query ?? '').trim().toLowerCase().split(/\s+/).filter(Boolean);
            const haystack = String(searchBlob ?? '').toLowerCase();

            if (this.activeChip !== 'all' && chipValue !== null && String(chipValue) !== String(this.activeChip)) {
                return false;
            }

            return tokens.every((token) => haystack.includes(token));
        },
    }));
});

export { discoveryFetchResults };
