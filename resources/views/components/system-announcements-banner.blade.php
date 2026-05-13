@props(['announcements'])

@if ($announcements->isNotEmpty())
    <div class="mb-4 space-y-2" x-data="{
        key: 'pb.dismissedAnnouncements',
        dismissed: [],
        init() {
            try {
                this.dismissed = JSON.parse(localStorage.getItem(this.key) || '[]');
            } catch (e) { this.dismissed = []; }
        },
        isHidden(id) { return this.dismissed.includes(id); },
        dismiss(id) {
            if (!this.dismissed.includes(id)) this.dismissed.push(id);
            localStorage.setItem(this.key, JSON.stringify(this.dismissed));
        }
    }">
        @foreach ($announcements as $ann)
            <div
                x-show="!isHidden({{ $ann->id }})"
                x-cloak
                class="rounded-xl border px-4 py-3 text-sm shadow-sm
                    @switch($ann->severity)
                        @case('critical') border-rose-300/80 bg-rose-50 text-rose-950 dark:border-rose-500/40 dark:bg-rose-950/40 dark:text-rose-50 @break
                        @case('warning') border-amber-300/80 bg-amber-50 text-amber-950 dark:border-amber-500/40 dark:bg-amber-950/35 dark:text-amber-50 @break
                        @default border-indigo-200/80 bg-indigo-50/90 text-slate-900 dark:border-indigo-500/35 dark:bg-indigo-950/40 dark:text-indigo-50
                    @endswitch
                "
                role="status"
            >
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="font-bold tracking-tight">{{ $ann->title }}</p>
                        <p class="mt-1 whitespace-pre-line text-[13px] leading-relaxed opacity-95">{{ $ann->body }}</p>
                        @if ($ann->starts_at || $ann->ends_at)
                            <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide opacity-80">
                                @if ($ann->starts_at)
                                    <span>From {{ $ann->starts_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>
                                @endif
                                @if ($ann->ends_at)
                                    <span class="ml-2">· Until {{ $ann->ends_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>
                                @endif
                            </p>
                        @endif
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg p-1 opacity-70 hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        @click="dismiss({{ $ann->id }})"
                        aria-label="Dismiss announcement"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
