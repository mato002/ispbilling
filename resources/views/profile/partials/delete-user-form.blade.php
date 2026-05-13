<section class="pb-card overflow-hidden border-rose-200/70 dark:border-rose-500/20">
    <div class="flex flex-col gap-4 border-l-4 border-rose-500/80 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            </span>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">
                        {{ __('Danger zone — Delete account') }}
                    </h2>
                    <span class="pb-pill pb-pill-bad">Irreversible</span>
                </div>
                <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently removed. Before deleting, please download anything you wish to retain.') }}
                </p>
            </div>
        </div>

        <button
            type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="pb-btn justify-center border-rose-300 bg-rose-50 text-rose-700 hover:border-rose-400 hover:bg-rose-100 hover:text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20 dark:hover:text-rose-200 sm:w-auto"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
            {{ __('Delete account') }}
        </button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-300">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ __('Are you sure you want to delete your account?') }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                    </p>
                </div>
            </div>

            <div class="mt-5">
                <label for="password" class="isp-form-label">{{ __('Confirm with your password') }}</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
                    </span>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="isp-form-input pl-9"
                        placeholder="{{ __('Enter your current password') }}"
                    />
                </div>
                @if($errors->userDeletion->get('password'))
                    <p class="mt-1 text-xs text-rose-500">{{ $errors->userDeletion->first('password') }}</p>
                @endif
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="pb-btn justify-center"
                >
                    {{ __('Cancel') }}
                </button>

                <button
                    type="submit"
                    class="pb-btn justify-center border-transparent bg-rose-600 text-white shadow-[0_8px_18px_-10px_rgba(244,63,94,0.55)] hover:bg-rose-700 hover:text-white"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                    {{ __('Yes, delete my account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
