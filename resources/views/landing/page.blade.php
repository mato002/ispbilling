<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo-meta
        :title="$pageMeta['title'].' · '.config('app.name', 'PrimeBilling')"
        :description="$pageMeta['description']"
        :canonical="route('landing.'.$page)"
    />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto-condensed:300,400,500,600,700,800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <x-vite-assets :assets="['resources/css/landing.css', 'resources/js/landing.js']" />
    @endif
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="pb-landing antialiased">
    @include('landing.partials.nav')
    <main class="min-h-screen overflow-x-hidden pb-16 pt-24 sm:pt-28">
        <section class="px-4 pb-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-900">← Home</a>
                <div class="mt-5 max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wider text-blue-600">{{ $pageMeta['title'] }}</p>
                    <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-5xl">{{ $pageMeta['headline'] }}</h1>
                    <p class="mt-4 text-base leading-relaxed text-slate-600 sm:text-lg">{{ $pageMeta['description'] }}</p>
                </div>
            </div>
        </section>
        @include('landing.pages.'.$page)
        <section class="px-4 pt-6 sm:px-6 lg:px-8">
            <div class="pb-cta-band mx-auto max-w-7xl rounded-3xl px-6 py-10 text-center text-white sm:px-12">
                <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Ready to run your ISP from one workspace?</h2>
                <p class="mx-auto mt-3 max-w-2xl text-sm text-blue-100">Start a {{ $defaultTrialDays }}-day trial and explore the platform with your team.</p>
                @guest
                    <a href="{{ Route::has('register.isp') ? route('register.isp') : route('register') }}" class="mt-6 inline-flex rounded-xl bg-white px-6 py-3 text-sm font-bold text-blue-700 shadow hover:bg-blue-50">Start free trial</a>
                @else
                    <a href="{{ url('/dashboard') }}" class="mt-6 inline-flex rounded-xl bg-white px-6 py-3 text-sm font-bold text-blue-700 shadow hover:bg-blue-50">Open dashboard</a>
                @endguest
            </div>
        </section>
    </main>
    @include('landing.partials.footer')
</body>
</html>
