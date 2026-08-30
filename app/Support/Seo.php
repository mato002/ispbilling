<?php

namespace App\Support;

use App\Models\ISP;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Seo
{
    public static function appName(): string
    {
        return (string) config('app.name', 'PrimeBilling');
    }

    public static function siteUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public static function absoluteUrl(?string $path = null): string
    {
        if ($path === null || $path === '') {
            return url('/');
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url($path);
    }

    /**
     * @return array{title: string, description: string, keywords: string|null, robots: string, og_type: string}
     */
    public static function page(string $key): array
    {
        $pages = config('seo.pages', []);
        $defaults = config('seo.defaults', []);
        $page = $pages[$key] ?? [];

        $title = self::replacePlaceholders((string) ($page['title'] ?? $defaults['title'] ?? self::appName()));
        $description = self::replacePlaceholders(
            (string) ($page['description'] ?? config('seo.default_description'))
        );

        return [
            'title' => $title,
            'description' => Str::limit(trim(strip_tags($description)), 160, ''),
            'keywords' => isset($page['keywords'])
                ? self::replacePlaceholders((string) $page['keywords'])
                : config('seo.keywords'),
            'robots' => (string) ($page['robots'] ?? 'index, follow'),
            'og_type' => (string) ($page['og_type'] ?? 'website'),
        ];
    }

    public static function ogImage(?string $override = null): string
    {
        $image = $override ?? config('seo.og_image');

        if ($image) {
            return self::absoluteUrl($image);
        }

        return asset('images/primebilling-mark.svg');
    }

    public static function replacePlaceholders(string $value): string
    {
        return str_replace(
            [':app', ':year'],
            [self::appName(), (string) date('Y')],
            $value
        );
    }

    /**
     * @return list<array{@type: string, position: int, name: string, item: string}>
     */
    public static function breadcrumbItems(array $crumbs): array
    {
        $items = [];
        $position = 1;

        foreach ($crumbs as $name => $url) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $name,
                'item' => self::absoluteUrl($url),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public static function breadcrumbSchema(array $crumbs): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => self::absoluteUrl().'#breadcrumb',
            'itemListElement' => self::breadcrumbItems($crumbs),
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public static function faqItems(): array
    {
        return config('seo.faq', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function faqSchema(): ?array
    {
        $items = self::faqItems();

        if ($items === []) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            '@id' => self::absoluteUrl().'#faq',
            'mainEntity' => array_map(static function (array $item): array {
                return [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ];
            }, $items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organizationSchema(?ISP $isp = null): array
    {
        if ($isp) {
            $schema = [
                '@type' => 'LocalBusiness',
                '@id' => url('/').'#organization',
                'name' => $isp->name,
                'url' => url('/'),
                'description' => 'Internet service provider — browse packages, pay online, and manage your account.',
            ];

            if ($logo = $isp->logoPublicUrl()) {
                $schema['logo'] = $logo;
                $schema['image'] = $logo;
            }

            if ($isp->address) {
                $schema['address'] = [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $isp->address,
                    'addressCountry' => config('seo.organization.country', 'KE'),
                ];
            }

            if ($isp->phone) {
                $schema['telephone'] = $isp->phone;
            }

            if ($isp->email) {
                $schema['email'] = $isp->email;
            }

            return $schema;
        }

        $org = config('seo.organization', []);

        $schema = [
            '@type' => 'Organization',
            '@id' => self::siteUrl().'/#organization',
            'name' => $org['name'] ?? self::appName(),
            'url' => self::siteUrl(),
            'logo' => self::ogImage(),
            'description' => config('seo.default_description'),
        ];

        if (! empty($org['legal_name'])) {
            $schema['legalName'] = $org['legal_name'];
        }

        if (! empty($org['contact_email'])) {
            $schema['email'] = $org['contact_email'];
        }

        if (! empty($org['contact_phone'])) {
            $schema['telephone'] = $org['contact_phone'];
        }

        $sameAs = array_values(array_filter($org['same_as'] ?? []));

        if ($sameAs !== []) {
            $schema['sameAs'] = $sameAs;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public static function softwareApplicationSchema(): array
    {
        return [
            '@type' => 'SoftwareApplication',
            '@id' => self::siteUrl().'/#software',
            'name' => self::appName(),
            'applicationCategory' => 'BusinessApplication',
            'applicationSubCategory' => 'ISP Billing Software',
            'operatingSystem' => 'Web',
            'description' => config('seo.default_description'),
            'featureList' => config('seo.features', []),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => config('seo.organization.currency', 'KES'),
                'description' => 'Free trial available — contact for enterprise pricing',
                'url' => Route::has('register.isp') ? route('register.isp') : self::siteUrl(),
            ],
            'publisher' => [
                '@id' => self::siteUrl().'/#organization',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function webSiteSchema(?ISP $isp = null): array
    {
        $schema = [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => $isp?->name ?? self::appName(),
            'url' => url('/'),
            'description' => $isp
                ? 'Customer portal for '.$isp->name.' — internet packages, billing, and account management.'
                : config('seo.default_description'),
            'publisher' => [
                '@id' => ($isp ? url('/') : self::siteUrl()).'/#organization',
            ],
            'inLanguage' => str_replace('_', '-', (string) config('seo.locale', 'en_KE')),
        ];

        if (! $isp && Route::has('register.isp')) {
            $schema['potentialAction'] = [
                '@type' => 'RegisterAction',
                'target' => route('register.isp'),
                'name' => 'Start free ISP trial',
            ];
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public static function webPageSchema(string $title, string $description, ?string $url = null): array
    {
        return [
            '@type' => 'WebPage',
            '@id' => ($url ?? url()->current()).'#webpage',
            'url' => $url ?? url()->current(),
            'name' => $title,
            'description' => Str::limit(trim(strip_tags($description)), 160, ''),
            'isPartOf' => ['@id' => url('/').'#website'],
            'about' => ['@id' => self::siteUrl().'/#organization'],
            'inLanguage' => str_replace('_', '-', (string) config('seo.locale', 'en_KE')),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $extra
     * @return array<string, mixed>
     */
    public static function graph(array $extra = []): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => $extra,
        ];
    }
}
