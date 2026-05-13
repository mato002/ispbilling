@extends('layouts.admin')

@section('title', 'Activity Logs · Audit Center')

@section('header')
<div class="space-y-4">
    <div class="isp-audit-hero pb-card border-slate-200/80 bg-gradient-to-br from-slate-50 via-white to-indigo-50/40 p-5 sm:p-6 dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950/30">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-indigo-600 dark:text-indigo-300">ISP Operations</span>
                    <span class="rounded-full border border-emerald-200/80 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/50 dark:text-emerald-300">
                        <span class="pb-pulse-dot mr-1.5 align-middle"></span>
                        Live audit stream
                    </span>
                </div>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Activity logs</h1>
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                    Enterprise audit trail for your ISP — staff actions, billing, network, and security events with full accountability.
                </p>
                <p class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-500">
                    <span class="isp-audit-pulse-bar" aria-hidden="true"></span>
                    Monitoring ISP operations · timestamps in your app timezone
                </p>
            </div>
            @php
                $exportQuery = http_build_query(request()->except('page'));
                $exportCsvUrl = route('isp.logs.activity.export.csv').($exportQuery !== '' ? '?'.$exportQuery : '');
                $exportPdfUrl = route('isp.logs.activity.export.pdf').($exportQuery !== '' ? '?'.$exportQuery : '');
            @endphp
            <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
                <a href="{{ $exportCsvUrl }}" class="pb-btn" title="Up to 2,000 rows matching current filters">
                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </a>
                <a href="{{ $exportPdfUrl }}" class="pb-btn" title="Up to 150 rows matching current filters">
                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
                <button type="button" class="pb-btn pb-btn-primary" onclick="window.print()">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                <a href="{{ route('isp.dashboard') }}" class="pb-btn">Dashboard</a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            <div class="isp-audit-kpi">
                <span class="isp-audit-kpi__label">Events today</span>
                <span class="isp-audit-kpi__value">{{ number_format($auditStats['events_today']) }}</span>
            </div>
            <div class="isp-audit-kpi isp-audit-kpi--alert">
                <span class="isp-audit-kpi__label">System alerts</span>
                <span class="isp-audit-kpi__value">{{ number_format($auditStats['security_alerts']) }}</span>
            </div>
            <div class="isp-audit-kpi">
                <span class="isp-audit-kpi__label">Failed logins</span>
                <span class="isp-audit-kpi__value">{{ number_format($auditStats['failed_logins']) }}</span>
            </div>
            <div class="isp-audit-kpi">
                <span class="isp-audit-kpi__label">Customer actions</span>
                <span class="isp-audit-kpi__value">{{ number_format($auditStats['customer_actions']) }}</span>
            </div>
            <div class="isp-audit-kpi">
                <span class="isp-audit-kpi__label">Billing changes</span>
                <span class="isp-audit-kpi__value">{{ number_format($auditStats['billing_changes']) }}</span>
            </div>
            <div class="isp-audit-kpi">
                <span class="isp-audit-kpi__label">Router / edge</span>
                <span class="isp-audit-kpi__value">{{ number_format($auditStats['router_changes']) }}</span>
            </div>
            <div class="isp-audit-kpi isp-audit-kpi--stable">
                <span class="isp-audit-kpi__label">System stable</span>
                <span class="isp-audit-kpi__value">{{ number_format($systemStablePct, 1) }}%</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
@php
    $filterCount = collect(request()->only(['search','action','user_id','model_type','severity','module','event_type','date_from','date_to']))->filter()->count();
@endphp

<div class="isp-audit-print-root space-y-5 pb-8">
    @if(!empty($isDemoStream))
        <div class="rounded-xl border border-amber-200/90 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100">
            <strong class="font-semibold">Development preview</strong> — sample audit stream shown because the activity log is empty. Data is not persisted; detail links are disabled.
        </div>
    @endif

    @if($tickerEvents->isNotEmpty())
        <div class="pb-card isp-audit-ticker-card overflow-hidden border-slate-200/80 p-0 dark:border-slate-800">
            <div class="flex items-center gap-2 border-b border-slate-200/80 bg-slate-50/90 px-4 py-2 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-400">
                <span class="pb-pulse-dot" aria-hidden="true"></span>
                Latest events
            </div>
            <div class="isp-audit-ticker-mask">
                <div class="isp-audit-ticker-track">
                    @foreach([$tickerEvents, $tickerEvents] as $chunk)
                        @foreach($chunk as $te)
                            <span class="isp-audit-ticker__item">
                                <span class="font-mono text-slate-400">{{ $te->created_at?->format('H:i:s') }}</span>
                                <span class="text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::limit($te->description, 72) }}</span>
                            </span>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-start">
        <div class="space-y-5 lg:col-span-8 xl:col-span-9 isp-audit-main">
            <div class="pb-card isp-audit-toolbar border-slate-200/80 p-4 sm:p-5 dark:border-slate-800">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Filter audit stream</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($filterCount > 0)
                            <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-[11px] font-semibold text-indigo-800 dark:bg-indigo-950/80 dark:text-indigo-200">{{ $filterCount }} active</span>
                        @endif
                        <a href="{{ route('isp.logs.activity.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Clear all</a>
                    </div>
                </div>
                <form action="{{ route('isp.logs.activity.index') }}" method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12 lg:gap-3">
                        <div class="lg:col-span-4">
                            <label class="isp-form-label flex items-center gap-1">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                Search
                            </label>
                            <input type="search" name="search" value="{{ request('search') }}" class="isp-form-input" placeholder="Description, action, IP…" autocomplete="off">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="isp-form-label">Severity</label>
                            <select name="severity" class="isp-form-select">
                                <option value="">All</option>
                                @foreach(\App\Support\ActivityLogAudit::SEVERITIES as $sev)
                                    <option value="{{ $sev }}" @selected(request('severity') === $sev)>{{ ucfirst($sev) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="isp-form-label">Module</label>
                            <select name="module" class="isp-form-select">
                                <option value="">All</option>
                                @foreach(\App\Support\ActivityLogAudit::MODULES as $key => $label)
                                    <option value="{{ $key }}" @selected(request('module') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="isp-form-label">Event type</label>
                            <select name="event_type" class="isp-form-select">
                                <option value="">All</option>
                                <option value="authentication" @selected(request('event_type') === 'authentication')>Authentication</option>
                                <option value="crud" @selected(request('event_type') === 'crud')>Create / update / delete</option>
                                <option value="billing" @selected(request('event_type') === 'billing')>Billing</option>
                                <option value="network" @selected(request('event_type') === 'network')>Network</option>
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="isp-form-label">Staff</label>
                            <select name="user_id" class="isp-form-select">
                                <option value="">All users</option>
                                @foreach($staffUsers as $u)
                                    <option value="{{ $u->id }}" @selected((string)request('user_id') === (string)$u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12 lg:gap-3">
                        <div class="lg:col-span-2">
                            <label class="isp-form-label">Action</label>
                            <input type="text" name="action" value="{{ request('action') }}" class="isp-form-input font-mono text-xs" placeholder="login, update…">
                        </div>
                        <div class="lg:col-span-4">
                            <label class="isp-form-label">Model class</label>
                            <select name="model_type" class="isp-form-select font-mono text-xs">
                                <option value="">Any model</option>
                                @foreach($distinctModelTypes as $mt)
                                    <option value="{{ $mt }}" @selected(request('model_type') === $mt)>{{ class_basename($mt) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="isp-form-label">From</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="isp-form-input">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="isp-form-label">To</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="isp-form-input">
                        </div>
                        <div class="flex items-end gap-2 lg:col-span-2">
                            <button type="submit" class="pb-btn pb-btn-primary w-full justify-center sm:w-auto">Apply filters</button>
                        </div>
                    </div>
                </form>
            </div>

            @if($logs->isEmpty())
                <div class="pb-card isp-audit-empty border-dashed border-slate-300/80 p-8 text-center dark:border-slate-600">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
                        <svg class="h-7 w-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">No matching audit events</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-600 dark:text-slate-400">
                        Adjust filters or widen the date range. All retained events remain immutable for compliance — only your view is filtered.
                    </p>
                    <a href="{{ route('isp.logs.activity.index') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Reset filters</a>
                </div>
            @else
                <div class="space-y-3">
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Audit timeline</h2>
                    <div class="isp-audit-timeline space-y-3">
                        @foreach($logs as $log)
                            @php
                                $severity = \App\Support\ActivityLogAudit::severity($log);
                                $moduleKey = \App\Support\ActivityLogAudit::moduleKey($log);
                                $category = \App\Support\ActivityLogAudit::eventCategory($log);
                                $title = \App\Support\ActivityLogAudit::eventTitle($log);
                                $isDemo = ($log->metadata['demo'] ?? false) === true;
                            @endphp
                            <article class="isp-audit-card pb-card group relative overflow-hidden border-slate-200/80 transition hover:border-indigo-200/80 hover:shadow-md dark:border-slate-800 dark:hover:border-indigo-900/50">
                                <div class="isp-audit-card__accent isp-audit-card__accent--{{ $severity }}"></div>
                                <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5">
                                    <div class="flex min-w-0 gap-4">
                                        <div class="isp-audit-icon isp-audit-icon--{{ $moduleKey }}" aria-hidden="true">
                                            @include('isp.logs.partials.audit-icon', ['module' => $moduleKey])
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="isp-audit-module isp-audit-module--{{ $moduleKey }}">{{ \App\Support\ActivityLogAudit::MODULES[$moduleKey] ?? $moduleKey }}</span>
                                                <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">[{{ $category }}]</span>
                                                <span class="isp-audit-sev isp-audit-sev--{{ $severity }}">{{ strtoupper($severity) }}</span>
                                            </div>
                                            <h3 class="mt-1.5 text-base font-bold leading-snug text-slate-900 dark:text-white">{{ $title }}</h3>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ Str::limit($log->description, 220) }}</p>
                                            <dl class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                                <div class="flex items-center gap-1">
                                                    <dt class="font-semibold uppercase tracking-wider text-slate-400">User</dt>
                                                    <dd class="font-medium text-slate-700 dark:text-slate-200">{{ $log->user?->name ?? '—' }}</dd>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <dt class="font-semibold uppercase tracking-wider text-slate-400">IP</dt>
                                                    <dd class="font-mono text-slate-700 dark:text-slate-200">{{ $log->ip_address ?? '—' }}</dd>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <dt class="font-semibold uppercase tracking-wider text-slate-400">Action</dt>
                                                    <dd class="font-mono text-slate-700 dark:text-slate-200">{{ $log->action }}</dd>
                                                </div>
                                            </dl>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 flex-col items-start gap-2 border-t border-slate-100 pt-3 sm:border-0 sm:pt-0 sm:text-right dark:border-slate-800">
                                        <time class="text-xs font-semibold tabular-nums text-slate-500 dark:text-slate-400" datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('M j, Y · H:i:s') }}</time>
                                        <span class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400">{{ $log->created_at?->diffForHumans() }}</span>
                                        @if(!$isDemo)
                                            <a href="{{ route('isp.logs.activity.show', $log) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View detail →</a>
                                        @else
                                            <span class="text-xs font-medium text-slate-400">Preview row</span>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
                <div class="pb-card p-4 print:hidden">{{ $logs->withQueryString()->links() }}</div>
            @endif
        </div>

        <aside class="space-y-4 lg:col-span-4 xl:col-span-3 isp-audit-aside print:hidden">
            <div class="pb-card isp-audit-side border-slate-200/80 p-4 dark:border-slate-800">
                <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Operations pulse</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Live posture for your team and edge infrastructure.</p>
                <ul class="mt-4 space-y-3">
                    <li class="isp-audit-side__row">
                        <span class="text-slate-500">Active staff</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ number_format($sidebarMetrics['active_staff']) }}</span>
                    </li>
                    <li class="isp-audit-side__row">
                        <span class="text-slate-500">Your last login (logged)</span>
                        <span class="text-right text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ $sidebarMetrics['last_login_at'] ? \Carbon\Carbon::parse($sidebarMetrics['last_login_at'])->diffForHumans() : '—' }}
                        </span>
                    </li>
                    <li class="isp-audit-side__row">
                        <span class="text-slate-500">Failed auth (today)</span>
                        <span class="font-bold text-amber-700 dark:text-amber-300">{{ number_format($sidebarMetrics['failed_auth_today']) }}</span>
                    </li>
                    <li class="isp-audit-side__row">
                        <span class="text-slate-500">Routers (active)</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ number_format($sidebarMetrics['routers_active']) }}</span>
                    </li>
                    <li class="isp-audit-side__row">
                        <span class="text-slate-500">System / staff changes today</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ number_format($sidebarMetrics['system_changes_today']) }}</span>
                    </li>
                </ul>
            </div>
            <div class="pb-card border border-slate-200/80 bg-slate-50/80 p-4 text-xs leading-relaxed text-slate-600 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-400">
                <strong class="font-semibold text-slate-800 dark:text-slate-200">Compliance note</strong>
                <p class="mt-2">Audit entries are append-only in normal operation. Export and retention policies can be layered for regulators and insurers.</p>
            </div>
        </aside>
    </div>
</div>
@endsection
