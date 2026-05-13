<section class="pb-card p-5 sm:p-6 h-full"
         x-data="{
            showCurrent: false,
            showNew: false,
            showConfirm: false,
            password: '',
            confirm: '',
            submitting: false,
            strength() {
                const p = this.password || '';
                let score = 0;
                if (p.length >= 8)  score++;
                if (p.length >= 12) score++;
                if (/[A-Z]/.test(p)) score++;
                if (/[0-9]/.test(p)) score++;
                if (/[^A-Za-z0-9]/.test(p)) score++;
                return Math.min(score, 4);
            },
            strengthLabel() {
                if (!this.password) return '';
                return ['Very weak','Weak','Fair','Strong','Excellent'][this.strength()];
            },
            strengthClass() {
                return ['bg-rose-500','bg-rose-400','bg-amber-400','bg-emerald-400','bg-emerald-500'][this.strength()];
            },
            strengthWidth() { return ((this.strength() + (this.password ? 1 : 0)) / 5) * 100 + '%'; },
            matches() { return this.password.length > 0 && this.password === this.confirm; }
         }">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-300">
                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                </span>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">{{ __('Security & password') }}</h2>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Use a long, unique password to keep your ISP workspace safe.') }}
            </p>
        </div>
        <span class="pb-pill pb-pill-info">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 6v6c0 5 3.5 9 8 10 4.5-1 8-5 8-10V6l-8-4z"/></svg>
            Encrypted
        </span>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-5"
          x-on:submit="submitting = true">
        @csrf
        @method('put')

        {{-- Current password --}}
        <div>
            <label for="update_password_current_password" class="isp-form-label">{{ __('Current password') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
                </span>
                <input
                    id="update_password_current_password"
                    name="current_password"
                    :type="showCurrent ? 'text' : 'password'"
                    class="isp-form-input pl-9 pr-10"
                    autocomplete="current-password"
                    placeholder="••••••••••"
                />
                <button type="button" @click="showCurrent = !showCurrent"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        :title="showCurrent ? 'Hide' : 'Show'">
                    <svg x-show="!showCurrent" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="showCurrent" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6 0-10-7-10-7a18.7 18.7 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c6 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                </button>
            </div>
            @if($errors->updatePassword->get('current_password'))
                <p class="mt-1 text-xs text-rose-500">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        {{-- New password --}}
        <div>
            <label for="update_password_password" class="isp-form-label">{{ __('New password') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2 13 10"/><path d="m18 5 3 3"/><circle cx="8" cy="16" r="6"/></svg>
                </span>
                <input
                    id="update_password_password"
                    name="password"
                    :type="showNew ? 'text' : 'password'"
                    class="isp-form-input pl-9 pr-10"
                    autocomplete="new-password"
                    placeholder="At least 8 characters"
                    x-model="password"
                />
                <button type="button" @click="showNew = !showNew"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        :title="showNew ? 'Hide' : 'Show'">
                    <svg x-show="!showNew" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="showNew" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6 0-10-7-10-7a18.7 18.7 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c6 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                </button>
            </div>

            {{-- Strength meter --}}
            <div class="mt-2" x-show="password.length > 0" x-cloak>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                    <div class="h-full transition-all duration-200" :class="strengthClass()" :style="`width: ${strengthWidth()}`"></div>
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px]">
                    <span class="text-slate-500 dark:text-slate-400">Strength: <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="strengthLabel()"></span></span>
                    <span class="text-slate-400">12+ chars · upper · number · symbol</span>
                </div>
            </div>
            @if($errors->updatePassword->get('password'))
                <p class="mt-1 text-xs text-rose-500">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        {{-- Confirm password --}}
        <div>
            <label for="update_password_password_confirmation" class="isp-form-label">{{ __('Confirm new password') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                </span>
                <input
                    id="update_password_password_confirmation"
                    name="password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    class="isp-form-input pl-9 pr-10"
                    autocomplete="new-password"
                    placeholder="Re-enter new password"
                    x-model="confirm"
                />
                <button type="button" @click="showConfirm = !showConfirm"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg x-show="!showConfirm" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="showConfirm" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-6 0-10-7-10-7a18.7 18.7 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c6 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                </button>
            </div>
            <p class="mt-1 text-[11px]"
               x-show="confirm.length > 0" x-cloak
               :class="matches() ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500'"
               x-text="matches() ? 'Passwords match' : 'Passwords do not match'"></p>
            @if($errors->updatePassword->get('password_confirmation'))
                <p class="mt-1 text-xs text-rose-500">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>

        <div class="flex flex-col-reverse items-stretch gap-3 pt-2 sm:flex-row sm:items-center">
            <button type="submit"
                    :disabled="submitting"
                    class="pb-btn pb-btn-primary justify-center w-full sm:w-auto">
                <svg x-show="!submitting" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" opacity=".25"/><path d="M22 12a10 10 0 0 1-10 10"/></svg>
                <span x-text="submitting ? 'Updating…' : '{{ __('Update password') }}'">{{ __('Update password') }}</span>
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-cloak
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                    {{ __('Password updated') }}
                </p>
            @endif
        </div>
    </form>
</section>
