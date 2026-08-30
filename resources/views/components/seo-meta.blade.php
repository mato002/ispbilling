@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'imageAlt' => null,
    'canonical' => null,
    'robots' => null,
    'ogType' => null,
    'keywords' => null,
])

@php
    $appName = \App\Support\Seo::appName();
    $pageTitle = $title ?? config('seo.defaults.title', $appName);
    $pageDescription = \Illuminate\Support\Str::limit(
        trim(strip_tags($description ?? config('seo.default_description'))),
        160,
        ''
    );
    $canonicalUrl = $canonical ?? url()->current();
    $ogImage = \App\Support\Seo::ogImage($image);
    $ogImageAlt = $imageAlt ?? config('seo.og_image_alt');
    $pageKeywords = $keywords ?? config('seo.keywords');
    $robotsContent = $robots ?? config('seo.defaults.robots', 'index, follow');
    $ogTypeContent = $ogType ?? config('seo.defaults.og_type', 'website');
    $locale = str_replace('_', '-', (string) config('seo.locale', 'en_KE'));
    $localeBase = explode('-', $locale)[0];
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
@if($pageKeywords)
    <meta name="keywords" content="{{ $pageKeywords }}">
@endif
<meta name="author" content="{{ config('seo.author', $appName) }}">
<meta name="robots" content="{{ $robotsContent }}">
<meta name="googlebot" content="{{ $robotsContent }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="alternate" hreflang="{{ $localeBase }}" href="{{ $canonicalUrl }}">
<link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">

<meta property="og:type" content="{{ $ogTypeContent }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $ogImageAlt }}">
@if(config('seo.og_image_width'))
    <meta property="og:image:width" content="{{ config('seo.og_image_width') }}">
@endif
@if(config('seo.og_image_height'))
    <meta property="og:image:height" content="{{ config('seo.og_image_height') }}">
@endif
<meta property="og:locale" content="{{ $locale }}">

<meta name="twitter:card" content="summary_large_image">
@if(config('seo.twitter_handle'))
    <meta name="twitter:site" content="{{ config('seo.twitter_handle') }}">
    <meta name="twitter:creator" content="{{ config('seo.twitter_handle') }}">
@endif
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
<meta name="twitter:image:alt" content="{{ $ogImageAlt }}">

@if(config('seo.google_site_verification'))
    <meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
@endif
@if(config('seo.bing_site_verification'))
    <meta name="msvalidate.01" content="{{ config('seo.bing_site_verification') }}">
@endif

<link rel="icon" href="{{ asset('images/primebilling-mark.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('images/primebilling-mark.svg') }}">
<meta name="theme-color" content="#2563eb">
<meta name="format-detection" content="telephone=no">
