<?php

namespace App\Http\Controllers;

use App\Models\ISP;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SeoController extends Controller
{
    public function sitemap(Request $request): Response
    {
        /** @var \App\Models\ISP|null $landingIsp */
        $landingIsp = $request->attributes->get('landing_isp');

        $urls = $landingIsp
            ? $this->tenantUrls($landingIsp)
            : $this->platformUrls();

        $content = view('seo.sitemap', [
            'urls' => $urls,
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /isp',
            'Disallow: /dashboard',
            'Disallow: /profile',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /verify-email',
            'Disallow: /confirm-password',
            'Disallow: /api',
            'Disallow: /customer/dashboard',
            'Disallow: /customer/portal',
            'Disallow: /customer/checkout',
            'Disallow: /hotspot',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * @return list<array{loc: string, changefreq: string, priority: string}>
     */
    private function platformUrls(): array
    {
        $urls = [
            $this->entry(route('welcome'), 'weekly', '1.0'),
            $this->entry(route('landing.features'), 'monthly', '0.8'),
            $this->entry(route('landing.workflows'), 'monthly', '0.8'),
            $this->entry(route('landing.pricing'), 'monthly', '0.8'),
            $this->entry(route('landing.faq'), 'monthly', '0.7'),
            $this->entry(route('terms'), 'monthly', '0.5'),
            $this->entry(route('privacy'), 'monthly', '0.5'),
            $this->entry(route('documentation'), 'weekly', '0.7'),
        ];

        if (Route::has('register.isp')) {
            $urls[] = $this->entry(route('register.isp'), 'monthly', '0.8');
        }

        return $urls;
    }

    /**
     * @return list<array{loc: string, changefreq: string, priority: string}>
     */
    private function tenantUrls(ISP $isp): array
    {
        $urls = [
            $this->entry(url('/'), 'weekly', '1.0'),
            $this->entry(route('customer.login'), 'monthly', '0.6'),
        ];

        if (Route::has('customer.register')) {
            $urls[] = $this->entry(route('customer.register'), 'monthly', '0.7');
        }

        return $urls;
    }

    /**
     * @return array{loc: string, changefreq: string, priority: string}
     */
    private function entry(string $loc, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
