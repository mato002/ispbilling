@props(['isp' => null])

@php
    $appName = config('app.name', 'PrimeBilling');
    $siteUrl = config('app.url');
    $graphs = [];

    if ($isp) {
        $localBusiness = [
            '@type' => 'LocalBusiness',
            '@id' => url('/').'#organization',
            'name' => $isp->name,
            'url' => url('/'),
            'description' => 'Internet service provider — browse packages and manage your account online.',
        ];

        if ($logo = $isp->logoPublicUrl()) {
            $localBusiness['logo'] = $logo;
            $localBusiness['image'] = $logo;
        }
        if ($isp->address) {
            $localBusiness['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $isp->address,
            ];
        }
        if ($isp->phone) {
            $localBusiness['telephone'] = $isp->phone;
        }
        if ($isp->email) {
            $localBusiness['email'] = $isp->email;
        }

        $graphs[] = $localBusiness;
    } else {
        $graphs[] = [
            '@type' => 'Organization',
            '@id' => $siteUrl.'/#organization',
            'name' => $appName,
            'url' => $siteUrl,
            'logo' => asset('images/primebilling-mark.svg'),
            'description' => config('seo.default_description'),
        ];

        $graphs[] = [
            '@type' => 'SoftwareApplication',
            'name' => $appName,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'description' => config('seo.default_description'),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'description' => 'Contact for ISP SaaS pricing',
            ],
        ];
    }

    $graphs[] = [
        '@type' => 'WebSite',
        '@id' => url('/').'#website',
        'name' => $isp?->name ?? $appName,
        'url' => url('/'),
        'publisher' => ['@id' => ($isp ? url('/') : $siteUrl).'/#organization'],
    ];

    $payload = [
        '@context' => 'https://schema.org',
        '@graph' => $graphs,
    ];
@endphp

<script type="application/ld+json">{!! json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
