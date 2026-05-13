@php($title = 'Create operator account · PrimeBilling')

<x-guest-layout>
    <div class="mb-7">
        @if(Route::has('register.isp'))
        <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
            <strong>ISP workspace.</strong> To create your company tenant (operators, routers, billing), use
            <a href="{{ route('register.isp') }}" class="font-semibold text-indigo-700 underline hover:text-indigo-900 dark:text-indigo-300">Register your ISP</a>
            instead of this form.
        </div>
        @endif
        <div class="mb-1 inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50/70 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-indigo-700 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
            </svg>
            Onboard your ISP
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[26px]">
            Create your operator account
        </h2>
        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
            Get started in under 5 minutes. Connect MikroTik, M-Pesa and your subscriber base.
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('register') }}"
        x-data="{ showPassword: false, loading: false }"
        @submit="loading = true"
        class="space-y-4"
    >
        @csrf

        <div>
            <label for="name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                Full name
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </span>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                       placeholder="Jane Operator"
                       class="pb-input @error('name') !border-rose-400 @enderror">
            </div>
            @error('name')
                <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

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
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                       placeholder="you@yourisp.co.ke"
                       class="pb-input @error('email') !border-rose-400 @enderror">
            </div>
            @error('email')
                <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                Password
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password"
                       placeholder="Create a strong password"
                       class="pb-input pr-12 @error('password') !border-rose-400 @enderror">
                <button type="button" @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-indigo-600">
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

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                Confirm password
            </label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/>
                    </svg>
                </span>
                <input id="password_confirmation" name="password_confirmation" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password"
                       placeholder="Repeat your password"
                       class="pb-input @error('password_confirmation') !border-rose-400 @enderror">
            </div>
            @error('password_confirmation')
                <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <label for="terms" class="flex cursor-pointer items-start gap-2.5 select-none">
            <input id="terms" type="checkbox" required class="pb-check mt-0.5">
            <span class="text-sm text-slate-600 dark:text-slate-300">
                I agree to PrimeBilling's
                <a href="#" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">Terms of Service</a>
                and
                <a href="#" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">Privacy Policy</a>.
            </span>
        </label>

        <button type="submit" :disabled="loading" class="pb-cta w-full text-sm">
            <span x-show="!loading" class="inline-flex items-center gap-2">
                Create operator account
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"/>
                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                </svg>
                Creating your account…
            </span>
        </button>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400">
            Need an ISP workspace?
            <a href="{{ route('register.isp') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">Register your ISP</a>
            ·
            Already with us?
            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">Sign in</a>
        </p>
    </form>
</x-guest-layout>
