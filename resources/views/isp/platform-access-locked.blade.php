@php($title = 'Workspace suspended')

<x-guest-layout>
    <div class="text-left">
        <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">ISP workspace suspended</h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            {{ $isp->name }} is temporarily unavailable on this platform.
        </p>
        @if (! empty($message))
            <p class="mt-4 rounded-lg border border-amber-200/80 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">{{ $message }}</p>
        @else
            <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">Please contact PrimeBilling support to restore access.</p>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Sign out</button>
        </form>
    </div>
</x-guest-layout>
