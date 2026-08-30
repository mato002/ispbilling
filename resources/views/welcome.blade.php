<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-landing-seo-meta :isp="$landingIsp" />
        <x-seo-json-ld :isp="$landingIsp" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/landing.css', 'resources/js/landing.js'])
        @endif
        <style>[x-cloak]{display:none!important}</style>
    </head>
    <body class="pb-landing antialiased"@if($landingIsp?->brandPrimaryHex()) style="--pb-brand: {{ $landingIsp->brandPrimaryHex() }};"@endif>
        @include('landing.partials.nav')

        <main class="overflow-x-hidden">
            @if($landingIsp)
                @include('landing.tenant')
            @else
                @include('landing.platform')
            @endif
        </main>

        @include('landing.partials.footer')
    </body>
</html>
