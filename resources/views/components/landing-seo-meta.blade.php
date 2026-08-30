@props(['isp' => null])

@php
    $appName = config('app.name', 'PrimeBilling');
    if ($isp) {
        $pageTitle = $isp->name.' · Internet packages & customer login';
        $description = 'Browse internet plans and manage your account with '.$isp->name;
        if ($isp->address) {
            $description .= ' — '.$isp->address;
        }
        $image = $isp->logoPublicUrl();
        $keywords = implode(', ', array_filter([
            $isp->name,
            'internet packages',
            'hotspot login',
            'ISP billing',
            'buy internet',
        ]));
    } else {
        $pageTitle = $appName.' · Run your entire ISP from one platform';
        $description = config('seo.default_description');
        $image = null;
        $keywords = null;
    }
@endphp

<x-seo-meta
    :title="$pageTitle"
    :description="$description"
    :image="$image"
    :keywords="$keywords"
/>
