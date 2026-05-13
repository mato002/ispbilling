@php($title = ($landingIsp?->name ?? 'Customer').' · Sign in')

<x-guest-layout>
    <div class="mb-7">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[26px]">
            Customer sign in
        </h2>
        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
            @if($landingIsp)
                Sign in to your {{ $landingIsp->name }} account. Your username is scoped to this provider.
            @else
                Sign in with your hotspot username. For the best experience, use your ISP’s customer portal link (subdomain).
            @endif
        </p>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('customer.authenticate') }}" class="space-y-5">
        @csrf

        <div>
            <label for="username" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username"
                   class="pb-input @error('username') !border-rose-400 @enderror" placeholder="your.username">
            @error('username')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="pb-input @error('password') !border-rose-400 @enderror">
            @error('password')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white shadow hover:bg-indigo-700">
            Sign in
        </button>
    </form>

    @if($landingIsp && Route::has('customer.register'))
        <p class="mt-6 text-center text-sm text-slate-500">
            New customer?
            <a href="{{ route('customer.register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Create an account</a>
        </p>
    @endif
</x-guest-layout>
