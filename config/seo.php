<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locale & Defaults
    |--------------------------------------------------------------------------
    */

    'locale' => env('SEO_LOCALE', 'en_KE'),

    'author' => env('SEO_AUTHOR', env('APP_NAME', 'PrimeBilling')),

    'defaults' => [
        'title' => env('APP_NAME', 'PrimeBilling').' · Run your entire ISP from one platform',
        'robots' => 'index, follow',
        'og_type' => 'website',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Meta Description & Keywords
    |--------------------------------------------------------------------------
    */

    'default_description' => env(
        'SEO_DEFAULT_DESCRIPTION',
        'PrimeBilling is the ISP operating system for African providers — MikroTik RouterOS automation, M-Pesa STK Push billing, hotspot & PPPoE management, customer portals, SMS campaigns, and network analytics in one SaaS platform.'
    ),

    'keywords' => env(
        'SEO_KEYWORDS',
        'ISP billing software, MikroTik billing, RouterOS automation, M-Pesa ISP payments, STK Push billing, hotspot management, PPPoE billing, ISP SaaS, Kenya ISP software, African ISP platform, PrimeBilling, customer portal, voucher billing, network provisioning'
    ),

    /*
    |--------------------------------------------------------------------------
    | Open Graph & Social
    |--------------------------------------------------------------------------
    |
    | Use a 1200×630 PNG/JPG at SEO_OG_IMAGE for best social preview results.
    |
    */

    'og_image' => env('SEO_OG_IMAGE'),

    'og_image_width' => (int) env('SEO_OG_IMAGE_WIDTH', 1200),

    'og_image_height' => (int) env('SEO_OG_IMAGE_HEIGHT', 630),

    'og_image_alt' => env(
        'SEO_OG_IMAGE_ALT',
        'PrimeBilling — ISP billing, MikroTik automation, and M-Pesa payments'
    ),

    'twitter_handle' => env('SEO_TWITTER_HANDLE'),

    'google_site_verification' => env('SEO_GOOGLE_SITE_VERIFICATION'),

    'bing_site_verification' => env('SEO_BING_SITE_VERIFICATION'),

    /*
    |--------------------------------------------------------------------------
    | Organization (platform marketing)
    |--------------------------------------------------------------------------
    */

    'organization' => [
        'name' => env('SEO_ORG_NAME', env('APP_NAME', 'PrimeBilling')),
        'legal_name' => env('SEO_ORG_LEGAL_NAME'),
        'contact_email' => env('SEO_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS')),
        'contact_phone' => env('SEO_CONTACT_PHONE'),
        'country' => env('SEO_ORG_COUNTRY', 'KE'),
        'currency' => env('SEO_ORG_CURRENCY', 'KES'),
        'same_as' => array_values(array_filter([
            env('SEO_SOCIAL_TWITTER'),
            env('SEO_SOCIAL_FACEBOOK'),
            env('SEO_SOCIAL_LINKEDIN'),
            env('SEO_SOCIAL_YOUTUBE'),
            env('SEO_SOCIAL_GITHUB'),
        ])),
    ],

    /*
    |--------------------------------------------------------------------------
    | Page-specific SEO
    |--------------------------------------------------------------------------
    |
    | Placeholders: :app (app name), :year (current year)
    |
    */

    'pages' => [
        'welcome' => [
            'title' => ':app · ISP Billing, MikroTik Automation & M-Pesa Payments',
            'description' => 'Automate ISP billing, MikroTik hotspot & PPPoE provisioning, M-Pesa collections, customer portals, and network operations — built for African internet service providers.',
            'keywords' => 'ISP billing software, MikroTik automation, M-Pesa ISP, hotspot billing, PPPoE management, African ISP SaaS, PrimeBilling',
            'og_type' => 'website',
        ],
        'register_isp' => [
            'title' => 'Register your ISP · :app — Start your free trial',
            'description' => 'Create your ISP workspace on :app. Connect MikroTik routers, accept M-Pesa payments, and automate subscriber billing in minutes. Free trial — no credit card required.',
            'keywords' => 'register ISP, ISP SaaS signup, MikroTik billing trial, M-Pesa ISP software, PrimeBilling signup',
            'robots' => 'index, follow',
        ],
        'terms' => [
            'title' => 'Terms of Service · :app',
            'description' => 'Terms of Service for :app — acceptable use, accounts, billing, payments, service availability, and responsibilities for ISP operators using the platform.',
            'keywords' => 'PrimeBilling terms, ISP software terms of service, SaaS terms',
            'og_type' => 'article',
        ],
        'privacy' => [
            'title' => 'Privacy Policy · :app',
            'description' => 'How :app collects, stores, and protects personal data for ISP operators, staff, and end customers — privacy policy and data handling practices.',
            'keywords' => 'PrimeBilling privacy policy, ISP data protection, customer data privacy',
            'og_type' => 'article',
        ],
        'documentation' => [
            'title' => 'Documentation · :app — Setup, MikroTik & M-Pesa guides',
            'description' => 'Complete :app documentation — installation, MikroTik RouterOS setup, M-Pesa Daraja integration, PPPoE & hotspot provisioning, API reference, and troubleshooting.',
            'keywords' => 'PrimeBilling docs, MikroTik setup guide, M-Pesa ISP integration, PPPoE billing tutorial, hotspot API',
            'og_type' => 'article',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SoftwareApplication feature list (JSON-LD)
    |--------------------------------------------------------------------------
    */

    'features' => [
        'MikroTik RouterOS API automation',
        'M-Pesa STK Push and Paybill reconciliation',
        'PPPoE and hotspot subscriber management',
        'Automated suspend and reconnect on payment',
        'Customer self-service portal',
        'Invoicing, subscriptions, and voucher sales',
        'SMS payment reminders and campaigns',
        'Multi-tenant ISP SaaS with role-based access',
        'Provisioning hub and router migration tools',
        'Network analytics, sessions, and data usage',
    ],

    /*
    |--------------------------------------------------------------------------
    | FAQ (landing page + FAQPage schema)
    |--------------------------------------------------------------------------
    */

    'faq' => [
        [
            'question' => 'Does PrimeBilling support MikroTik?',
            'answer' => 'Yes. PrimeBilling is built around MikroTik RouterOS — hotspot users, PPPoE secrets, queue profiles, IP pools, API provisioning, fleet health polling, backups, and router migration.',
        ],
        [
            'question' => 'Does PrimeBilling support M-Pesa?',
            'answer' => 'Yes. Configure Safaricom Daraja STK Push, Paybill, or Till workflows. Payments reconcile automatically and can trigger subscriber activation, renewal, and reconnection.',
        ],
        [
            'question' => 'Can I automate subscriber suspensions?',
            'answer' => 'Yes. Overdue invoices can trigger SMS reminders, grace periods, automatic MikroTik suspension, and instant reconnection when payment is received.',
        ],
        [
            'question' => 'Can ISP customers self-serve?',
            'answer' => 'Yes. Each ISP gets a branded customer portal where subscribers renew packages, view invoices, download receipts, check usage, and raise support tickets.',
        ],
        [
            'question' => 'Do you support inventory and field technicians?',
            'answer' => 'Yes. Track CPE with QR codes, assign devices to technicians, and link equipment to customer installations from the operations dashboard.',
        ],
        [
            'question' => 'Is there a free trial?',
            'answer' => 'Yes. Start with a full-platform free trial — no credit card required. Connect your MikroTik router and go live typically within 24 hours.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap entries (platform host)
    |--------------------------------------------------------------------------
    */

    'sitemap' => [
        ['route' => 'welcome', 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['route' => 'register.isp', 'changefreq' => 'monthly', 'priority' => '0.9', 'optional' => true],
        ['route' => 'documentation', 'changefreq' => 'weekly', 'priority' => '0.8'],
        ['route' => 'terms', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ['route' => 'privacy', 'changefreq' => 'monthly', 'priority' => '0.5'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots.txt disallow paths
    |--------------------------------------------------------------------------
    */

    'robots_disallow' => [
        '/admin',
        '/isp',
        '/dashboard',
        '/profile',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/verify-email',
        '/confirm-password',
        '/api',
        '/customer/dashboard',
        '/customer/portal',
        '/customer/checkout',
        '/hotspot',
        '/storage',
        '/vendor',
    ],

];
