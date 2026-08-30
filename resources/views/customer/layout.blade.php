<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Customer portal') · {{ $customer->isp->name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Customer portal</p>
                <h1 class="text-lg font-bold text-slate-900">{{ $customer->isp->name ?? config('app.name') }}</h1>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden text-slate-600 sm:inline">{{ $customer->full_name ?? '' }}</span>
                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-200 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
        <nav class="mx-auto flex max-w-5xl gap-1 overflow-x-auto px-4 pb-3 text-sm font-medium">
            <a href="{{ route('customer.dashboard') }}"
               class="whitespace-nowrap rounded-lg px-3 py-1.5 {{ request()->routeIs('customer.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">Home</a>
            <a href="{{ route('customer.usage') }}"
               class="whitespace-nowrap rounded-lg px-3 py-1.5 {{ request()->routeIs('customer.usage') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">Usage</a>
            <a href="{{ route('customer.invoices') }}"
               class="whitespace-nowrap rounded-lg px-3 py-1.5 {{ request()->routeIs('customer.invoices') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">Bills</a>
            <a href="{{ route('customer.sessions') }}"
               class="whitespace-nowrap rounded-lg px-3 py-1.5 {{ request()->routeIs('customer.sessions') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">Sessions</a>
            <a href="{{ route('customer.tickets.index') }}"
               class="whitespace-nowrap rounded-lg px-3 py-1.5 {{ request()->routeIs('customer.tickets.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">Support</a>
        </nav>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
