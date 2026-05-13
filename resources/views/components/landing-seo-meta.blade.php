@props(['isp' => null])

@php
    $appName = config('app.name', 'PrimeBilling');
    if ($isp) {
        $pageTitle = $isp->name.' · Internet packages & customer login';
        $description = 'Browse plans and manage your account with '.$isp->name;
        if ($isp->address) {
            $description .= ' — '.$isp->address;
        }
    } else {
        $pageTitle = $appName.' · Run your entire ISP from one platform';
        $description = 'Billing, MikroTik automation, M-Pesa payments, hotspot & PPPoE management for ISPs.';
    }
    $description = \Illuminate\Support\Str::limit(trim(strip_tags($description)), 160);
    $canonical = url()->current();
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="website">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $description }}">
@if($isp && ($logo = $isp->logoPublicUrl()))
    <meta property="og:image" content="{{ $logo }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $description }}">
