@props([
    'logs',
    'emptyMessage' => 'No activity recorded yet.',
])

@php
    use App\Support\ActivityLogAudit;
@endphp

@if ($logs->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">{{ $emptyMessage }}</p>
@else
    <ol class="relative space-y-0 border-l border-slate-200 dark:border-slate-700 ml-2">
        @foreach ($logs as $log)
            @php
                $severity = ActivityLogAudit::severity($log);
                $tagClasses = match ($severity) {
                    'critical', 'danger' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:border-rose-500/30',
                    'warning' => 'bg-amber-50 text-amber-900 border-amber-200 dark:bg-amber-500/10 dark:text-amber-100 dark:border-amber-500/30',
                    'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:border-emerald-500/30',
                    default => 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:border-slate-600',
                };
                $actionLabel = ucwords(str_replace('_', ' ', (string) $log->action));
                $description = $log->description ?: ActivityLogAudit::eventTitle($log);
                $time = $log->created_at?->timezone(config('app.timezone'))->format('M j, g:i A');
            @endphp
            <li class="relative pl-6 pb-6 last:pb-0">
                <span class="absolute -left-[5px] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-indigo-500 shadow-sm dark:border-gray-800" aria-hidden="true"></span>
                <div class="rounded-lg border border-slate-100 bg-slate-50/50 p-3 dark:border-slate-700 dark:bg-slate-900/30">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $tagClasses }}">
                                {{ $actionLabel }}
                            </span>
                            @if ($log->user?->name)
                                <span class="ml-2 text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $log->user->name }}</span>
                            @endif
                        </div>
                        <time class="shrink-0 text-[11px] font-mono text-slate-400 dark:text-slate-500">{{ $time }}</time>
                    </div>
                    <p class="mt-2 text-sm text-gray-700 dark:text-gray-200 leading-snug">{{ $description }}</p>
                    <a href="{{ route('admin.audit.activity.show', $log) }}" class="mt-2 inline-block text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                        View event detail
                    </a>
                </div>
            </li>
        @endforeach
    </ol>
@endif
