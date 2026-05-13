@php($title = 'Join '.$landingIsp->name)

<x-guest-layout>
    <div class="mb-7">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-[26px]">
            Join {{ $landingIsp->name }}
        </h2>
        <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
            Create a hotspot customer account for this provider. After sign-up you can pick a package and pay.
        </p>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('customer.register.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Full name</label>
            <input name="full_name" type="text" value="{{ old('full_name') }}" required class="pb-input" placeholder="Jane Customer">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Phone</label>
            <input name="phone_number" type="text" value="{{ old('phone_number') }}" required class="pb-input" placeholder="2547XXXXXXXX">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Email (optional)</label>
            <input name="email" type="email" value="{{ old('email') }}" class="pb-input" placeholder="you@email.com">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Choose username</label>
            <input name="username" type="text" value="{{ old('username') }}" required class="pb-input" pattern="[a-zA-Z0-9._-]+" title="Letters, numbers, dot, underscore, hyphen">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Password</label>
            <input name="password" type="password" required class="pb-input" autocomplete="new-password" minlength="8">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Confirm password</label>
            <input name="password_confirmation" type="password" required class="pb-input" autocomplete="new-password" minlength="8">
        </div>

        <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Location (optional)</label>
            <input name="location" type="text" value="{{ old('location') }}" class="pb-input" placeholder="Town / estate">
        </div>

        <button type="submit" class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-bold text-white shadow hover:bg-indigo-700">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Already have an account?
        <a href="{{ route('customer.login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Sign in</a>
    </p>
</x-guest-layout>
