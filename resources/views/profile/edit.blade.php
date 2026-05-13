@extends('layouts.admin')

@section('title', 'Account Profile')

@php
    $user = $user ?? auth()->user();
    $ispName = $user->isp?->name ?? 'PrimeBilling Workspace';
    $roleLabel = $user->isSuperAdmin()
        ? 'Super Administrator'
        : ($user->isIspAdmin() ? 'ISP Administrator' : ($user->isIspStaff() ? 'ISP Staff' : 'Member'));
    $accessLevel = $user->isSuperAdmin()
        ? 'Platform Owner'
        : ($user->isIspAdmin() ? 'Workspace Admin' : 'Operations Staff');
    $accountActive = (bool) ($user->is_active ?? true);
    $verified = $user->email_verified_at !== null;
    $initials = strtoupper(collect(explode(' ', trim($user->name ?? 'U S')))
        ->filter()
        ->map(fn ($p) => substr($p, 0, 1))
        ->take(2)
        ->implode(''));
@endphp

@section('header')
    <x-isp-page-header
        title="Account Profile"
        module="Account & Security"
        description="Manage your personal details, security settings, and ISP access preferences."
    >
        <x-slot:actions>
            <span class="pb-pill pb-pill-info">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                {{ $roleLabel }}
            </span>
            <span class="pb-pill pb-pill-cyan">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 6v6c0 5 3.5 9 8 10 4.5-1 8-5 8-10V6l-8-4z"/></svg>
                Role-based access
            </span>
            <span class="pb-pill pb-pill-ok">
                <span class="pb-dot pb-dot-ok"></span>
                Secure account
            </span>
        </x-slot:actions>
    </x-isp-page-header>
@endsection

@section('content')
<div class="space-y-6">

    {{-- ============================================================
         1) PROFILE SUMMARY CARD
    ============================================================ --}}
    <section class="pb-card pb-fade-up p-5 sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4 min-w-0">
                <div class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-violet-500 to-cyan-500 text-lg font-bold text-white shadow-[0_12px_24px_-12px_rgba(99,102,241,0.55),inset_0_1px_0_rgba(255,255,255,0.18)]">
                    {{ $initials }}
                    <span class="absolute -bottom-0.5 -right-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full {{ $accountActive ? 'bg-emerald-400' : 'bg-rose-400' }} ring-2 ring-white dark:ring-slate-900"></span>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="truncate text-base font-bold text-slate-900 dark:text-white sm:text-lg">{{ $user->name }}</h2>
                        @if($accountActive)
                            <span class="pb-pill pb-pill-ok"><span class="pb-dot pb-dot-ok"></span> Active</span>
                        @else
                            <span class="pb-pill pb-pill-bad"><span class="pb-dot pb-dot-bad"></span> Suspended</span>
                        @endif
                        @if($verified)
                            <span class="pb-pill pb-pill-info">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                                Verified
                            </span>
                        @else
                            <span class="pb-pill pb-pill-warn">Unverified</span>
                        @endif
                    </div>
                    <p class="mt-1 truncate text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                    <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
                        {{ $roleLabel }} · {{ $ispName }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:max-w-xl">
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">Workspace</div>
                    <div class="mt-1 truncate text-sm font-semibold text-slate-800 dark:text-slate-100" title="{{ $ispName }}">{{ $ispName }}</div>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">Access level</div>
                    <div class="mt-1 truncate text-sm font-semibold text-indigo-600 dark:text-indigo-300">{{ $accessLevel }}</div>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">Member since</div>
                    <div class="mt-1 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                        {{ $user->created_at?->format('M Y') ?? '—' }}
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">Last sign-in</div>
                    <div class="mt-1 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                        {{ optional($user->updated_at)->diffForHumans() ?? 'Just now' }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         2) PROFILE INFORMATION + 3) SECURITY (2-column on desktop)
    ============================================================ --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="pb-fade-up delay-1">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="pb-fade-up delay-2">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    {{-- ============================================================
         4) ACCESS & PERMISSIONS
    ============================================================ --}}
    <section class="pb-card pb-fade-up delay-2 p-5 sm:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">Access & permissions</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Read-only summary of your role-based access and session security on this workspace.
                </p>
            </div>
            <span class="pb-pill pb-pill-info">Read-only</span>
        </header>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border border-slate-200/80 bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                    <svg class="h-3.5 w-3.5 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    Role
                </div>
                <div class="mt-1.5 text-sm font-semibold text-slate-900 dark:text-white">{{ $roleLabel }}</div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Defines which modules and actions you can perform across PrimeBilling.</p>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                    <svg class="h-3.5 w-3.5 text-cyan-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18M3 12h18M3 17h18"/></svg>
                    Workspace
                </div>
                <div class="mt-1.5 text-sm font-semibold text-slate-900 dark:text-white">{{ $ispName }}</div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">All your operations are scoped to this ISP tenant.</p>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                    <svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 6v6c0 5 3.5 9 8 10 4.5-1 8-5 8-10V6l-8-4z"/></svg>
                    Access level
                </div>
                <div class="mt-1.5 text-sm font-semibold text-slate-900 dark:text-white">{{ $accessLevel }}</div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Permissions enforced by tenant scoping & role policies.</p>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                    <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    Two-factor authentication
                </div>
                <div class="mt-1.5 flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                    <span class="pb-pill pb-pill-warn">Coming soon</span>
                </div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Add an extra security layer with TOTP. Will be available in a future release.</p>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                    <svg class="h-3.5 w-3.5 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    Session security
                </div>
                <div class="mt-1.5 text-sm font-semibold text-slate-900 dark:text-white">CSRF · Encrypted cookies</div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">All authenticated requests are protected with Laravel's built-in CSRF and session encryption.</p>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white/60 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                <div class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                    <svg class="h-3.5 w-3.5 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    Account status
                </div>
                <div class="mt-1.5 text-sm font-semibold text-slate-900 dark:text-white">
                    {{ $accountActive ? 'Active & in good standing' : 'Suspended' }}
                </div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Status reflects whether you can sign in and operate the platform.</p>
            </div>
        </div>
    </section>

    {{-- ============================================================
         5) PREFERENCES (placeholders)
    ============================================================ --}}
    <section class="pb-card pb-fade-up delay-3 p-5 sm:p-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">Preferences</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Personalise how PrimeBilling looks and behaves for you. Some preferences are stored locally for now.
                </p>
            </div>
            <span class="pb-pill">Beta</span>
        </header>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
             x-data="{
                 theme: localStorage.getItem('pb.theme') || 'system',
                 notifs: localStorage.getItem('pb.notifs') || 'all',
                 timezone: localStorage.getItem('pb.timezone') || @json($user->isp?->timezone ?? config('app.timezone', 'Africa/Nairobi')),
                 landing: localStorage.getItem('pb.landing') || 'dashboard',
                 save(key, value) { localStorage.setItem('pb.' + key, value); }
             }">
            <div>
                <label class="isp-form-label">Theme</label>
                <select class="isp-form-select" x-model="theme" @change="save('theme', theme); if(theme==='dark'){document.documentElement.classList.add('dark')} else if(theme==='light'){document.documentElement.classList.remove('dark')}">
                    <option value="system">Match system</option>
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                </select>
                <p class="mt-1 text-[11px] text-slate-400">Affects this browser only.</p>
            </div>

            <div>
                <label class="isp-form-label">Notifications</label>
                <select class="isp-form-select" x-model="notifs" @change="save('notifs', notifs)">
                    <option value="all">All activity</option>
                    <option value="critical">Critical only</option>
                    <option value="none">Mute</option>
                </select>
                <p class="mt-1 text-[11px] text-slate-400">Controls in-app notification volume.</p>
            </div>

            <div>
                <label class="isp-form-label">Timezone</label>
                <select class="isp-form-select" x-model="timezone" @change="save('timezone', timezone)">
                    <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
                    <option value="Africa/Lagos">Africa/Lagos (WAT)</option>
                    <option value="Africa/Kampala">Africa/Kampala (EAT)</option>
                    <option value="Africa/Dar_es_Salaam">Africa/Dar_es_Salaam (EAT)</option>
                    <option value="UTC">UTC</option>
                </select>
                <p class="mt-1 text-[11px] text-slate-400">Used to display dates in this browser.</p>
            </div>

            <div>
                <label class="isp-form-label">Default landing page</label>
                <select class="isp-form-select" x-model="landing" @change="save('landing', landing)">
                    <option value="dashboard">Dashboard</option>
                    <option value="customers">Customers</option>
                    <option value="invoices">Invoices</option>
                    <option value="payments">Payments</option>
                    <option value="routers">Routers</option>
                </select>
                <p class="mt-1 text-[11px] text-slate-400">Where to land after sign-in.</p>
            </div>
        </div>
    </section>

    {{-- ============================================================
         6) DANGER ZONE
    ============================================================ --}}
    <div class="pb-fade-up delay-4">
        @include('profile.partials.delete-user-form')
    </div>

</div>
@endsection
