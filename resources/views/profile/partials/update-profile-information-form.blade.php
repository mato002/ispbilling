@php
    $user = $user ?? auth()->user();
    $verified = $user->email_verified_at !== null;
@endphp

<section class="pb-card p-5 sm:p-6 h-full">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-300">
                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                </span>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">{{ __('Profile information') }}</h2>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __("Update your account's profile information and email address.") }}
            </p>
        </div>
        @if($verified)
            <span class="pb-pill pb-pill-ok">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                Verified
            </span>
        @else
            <span class="pb-pill pb-pill-warn">Email unverified</span>
        @endif
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-5 space-y-5"
          x-data="{ submitting: false }"
          x-on:submit="submitting = true">
        @csrf
        @method('patch')

        {{-- Name --}}
        <div>
            <label for="name" class="isp-form-label">{{ __('Full name') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                </span>
                <input
                    id="name"
                    name="name"
                    type="text"
                    class="isp-form-input pl-9"
                    value="{{ old('name', $user->name) }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Your full name"
                />
            </div>
            <p class="mt-1 text-[11px] text-slate-400">Shown on receipts, audit logs and tickets.</p>
            @error('name')
                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="isp-form-label">{{ __('Email address') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                </span>
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="isp-form-input pl-9"
                    value="{{ old('email', $user->email) }}"
                    required
                    autocomplete="username"
                    placeholder="you@example.com"
                />
            </div>
            <p class="mt-1 text-[11px] text-slate-400">Used for password resets and security alerts.</p>
            @error('email')
                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                    <span>{{ __('Your email address is unverified.') }}</span>
                    <button form="send-verification" class="ml-auto font-semibold underline hover:text-amber-900 dark:hover:text-amber-100">
                        {{ __('Resend verification email') }}
                    </button>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            @endif
        </div>

        {{-- Read-only context --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="isp-form-label">Role</label>
                <input type="text" class="isp-form-input bg-slate-50 dark:bg-slate-800/40" value="{{ $user->isSuperAdmin() ? 'Super Administrator' : ($user->isIspAdmin() ? 'ISP Administrator' : 'ISP Staff') }}" disabled>
            </div>
            <div>
                <label class="isp-form-label">Workspace</label>
                <input type="text" class="isp-form-input bg-slate-50 dark:bg-slate-800/40" value="{{ $user->isp?->name ?? 'PrimeBilling Workspace' }}" disabled>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex flex-col-reverse items-stretch gap-3 pt-2 sm:flex-row sm:items-center">
            <button type="submit"
                    :disabled="submitting"
                    class="pb-btn pb-btn-primary justify-center w-full sm:w-auto">
                <svg x-show="!submitting" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>
                <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" opacity=".25"/><path d="M22 12a10 10 0 0 1-10 10"/></svg>
                <span x-text="submitting ? 'Saving…' : '{{ __('Save changes') }}'">{{ __('Save changes') }}</span>
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-cloak
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                    {{ __('Saved successfully') }}
                </p>
            @endif
        </div>
    </form>
</section>
