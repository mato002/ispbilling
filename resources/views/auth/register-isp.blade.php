@php($title = 'Register your ISP · PrimeBilling')

<x-guest-layout compact>
    <div class="mb-4">
        <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Create your ISP workspace
        </h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            Choose a plan and billing details on the next step.
        </p>
    </div>

    <form method="POST" action="{{ route('register.isp') }}" x-data="{ showPassword: false, loading: false }" @submit="loading = true" class="space-y-3">
        @csrf

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">ISP display name</label>
                <input name="isp_name" type="text" value="{{ old('isp_name') }}" required class="pb-input @error('isp_name') !border-rose-400 @enderror" placeholder="Acme Fibre">
                @error('isp_name')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Legal / company name</label>
                <input name="company_name" type="text" value="{{ old('company_name') }}" required class="pb-input @error('company_name') !border-rose-400 @enderror">
                @error('company_name')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Work email</label>
                <input name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="pb-input @error('email') !border-rose-400 @enderror" placeholder="you@yourisp.co.ke">
                @error('email')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Company phone</label>
                <input name="phone" type="text" value="{{ old('phone') }}" required class="pb-input @error('phone') !border-rose-400 @enderror" placeholder="2547XXXXXXXX">
                @error('phone')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Your full name (administrator)</label>
                <input name="name" type="text" value="{{ old('name') }}" required class="pb-input @error('name') !border-rose-400 @enderror">
                @error('name')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Password</label>
                <input name="password" :type="showPassword ? 'text' : 'password'" required class="pb-input @error('password') !border-rose-400 @enderror" autocomplete="new-password">
                @error('password')<p class="mt-1 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Confirm password</label>
                <input name="password_confirmation" :type="showPassword ? 'text' : 'password'" required class="pb-input" autocomplete="new-password">
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
            <label class="flex cursor-pointer items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                <input type="checkbox" x-model="showPassword" class="rounded border-slate-300"> Show passwords
            </label>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 dark:text-indigo-300">Sign in</a>
                ·
                <a href="{{ route('register') }}" class="font-semibold text-indigo-600 dark:text-indigo-300">Staff registration</a>
            </p>
        </div>

        <button type="submit" :disabled="loading" class="pb-cta w-full !py-2.5 text-sm">Continue</button>
    </form>
</x-guest-layout>
