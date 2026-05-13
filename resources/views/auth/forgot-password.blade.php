@php($title = 'Reset password · PrimeBilling')

<x-guest-layout>
    <div class="mb-7">
        <div class="mb-1 inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50/70 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-indigo-700 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
            </svg>
            Account Recovery
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[26px]">
            Reset your password
        </h2>
        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
            Enter the email associated with your operator account and we'll send a secure reset link.
        </p>
    </div>

    @if(session('status'))
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            <svg class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('password.email') }}"
        x-data="{ loading: false }"
        @submit="loading = true"
        class="space-y-5"
    >
        @csrf

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
                    placeholder="you@yourisp.co.ke"
                    class="pb-input @error('email') !border-rose-400 focus:!border-rose-500 focus:!shadow-[0_0_0_4px_rgba(244,63,94,0.15)] @enderror"
                >
            </div>
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" :disabled="loading" class="pb-cta w-full text-sm">
            <span x-show="!loading" class="inline-flex items-center gap-2">
                Send password reset link
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/>
                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
                Sending secure link…
            </span>
        </button>

        <div class="text-center">
            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>
                </svg>
                Back to sign in
            </a>
        </div>
    </form>
</x-guest-layout>
