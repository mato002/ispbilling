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

        init() {
            if (! this.autoSearch) {
                return;
            }

            this.$el.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"])').forEach((field) => {
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
            this.$el.querySelector('[name="page"]')?.remove();

            if (typeof this.$el.requestSubmit === 'function') {
                this.$el.requestSubmit();
            } else {
                this.$el.submit();
            }
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
