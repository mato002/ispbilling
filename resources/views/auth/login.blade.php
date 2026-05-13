@php($title = 'Sign in to PrimeBilling')

<x-guest-layout>
    {{-- Header --}}
    <div class="mb-7">
        <div class="mb-1 inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50/70 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-indigo-700 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3Z"/>
            </svg>
            ISP Operator Console
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[26px]">
            Welcome back, Operator
        </h2>
        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
            Sign in to your ISP command center to manage subscribers, routers and revenue.
        </p>
    </div>

    {{-- Session Status --}}
    @if(session('status'))
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            <svg class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Aggregate auth error (e.g. invalid credentials) --}}
    @if($errors->any())
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
            <svg class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('login') }}"
        x-data="{
            showPassword: false,
            loading: false,
            submit() {
                if (this.loading) return;
                this.loading = true;
            }
        }"
        @submit="submit()"
        class="space-y-5"
        novalidate
    >
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                Work email
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>
                    </svg>
                </span>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    inputmode="email"
                    placeholder="you@yourisp.co.ke"
                    class="pb-input @error('email') !border-rose-400 focus:!border-rose-500 focus:!shadow-[0_0_0_4px_rgba(244,63,94,0.15)] @enderror"
                >
            </div>
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                <input
                    id="password"
                    name="password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="pb-input pr-12 @error('password') !border-rose-400 focus:!border-rose-500 focus:!shadow-[0_0_0_4px_rgba(244,63,94,0.15)] @enderror"
                >
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-indigo-600 focus:text-indigo-600 focus:outline-none dark:hover:text-indigo-300"
                >
                    <svg x-show="!showPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6.5 0-10-7-10-7a18.5 18.5 0 0 1 4.06-4.94"/>
                        <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 7 10 7a18.42 18.42 0 0 1-2.16 3.19"/>
                        <path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"/><path d="M1 1l22 22"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2 select-none">
                <input id="remember_me" type="checkbox" name="remember" class="pb-check">
                <span class="text-sm text-slate-600 dark:text-slate-300">Keep me signed in</span>
            </label>
            <span class="hidden items-center gap-1 text-[11px] font-medium text-slate-400 sm:inline-flex">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                256-bit TLS
            </span>
        </div>

        {{-- Submit --}}
        <button type="submit"
                :disabled="loading"
                class="pb-cta w-full text-sm">
            <span x-show="!loading" class="inline-flex items-center gap-2">
                Launch ISP Console
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/>
                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
                Authenticating securely…
            </span>
        </button>

        {{-- Divider --}}
        <div class="relative my-1">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200 dark:border-slate-700/70"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-white/0 px-2 text-[11px] font-medium uppercase tracking-wider text-slate-400">
                    New to PrimeBilling?
                </span>
            </div>
        </div>

        {{-- Register CTA --}}
        @if (Route::has('register'))
            <a href="{{ route('register') }}"
               class="group flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white/70 p-3.5 text-sm transition hover:border-indigo-300 hover:bg-white hover:shadow-sm dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-indigo-500/40 dark:hover:bg-slate-800/70">
                <span class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500/10 to-cyan-500/10 text-indigo-600 dark:text-indigo-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                        </svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block font-semibold text-slate-800 dark:text-slate-100">Create an operator account</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">Onboard your ISP in under 5 minutes</span>
                    </span>
                </span>
                <svg class="h-4 w-4 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </a>
        @endif
    </form>
</x-guest-layout>
