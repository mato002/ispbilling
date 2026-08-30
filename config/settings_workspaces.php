<?php

/**
 * @deprecated Superseded by config/navigation_workspaces.php — kept for legacy references.
 * Horizontal settings workspace tabs (shown at top of settings pages).
 */
return [

    'admin' => [
        [
            'label' => 'General',
            'route' => 'admin.settings.index',
            'active' => ['admin.settings.index'],
        ],
        [
            'label' => 'Form controls',
            'route' => 'admin.settings.forms.index',
            'active' => ['admin.settings.forms.*'],
        ],
        [
            'label' => 'Integrations',
            'route' => 'admin.integrations.index',
            'active' => ['admin.integrations.*'],
        ],
        [
            'label' => 'Promo codes',
            'route' => 'admin.promo-codes.index',
            'active' => ['admin.promo-codes.*'],
        ],
        [
            'label' => 'Security',
            'route' => 'admin.audit.security',
            'active' => ['admin.audit.security'],
        ],
    ],

    'isp' => [
        [
            'label' => 'Operations hub',
            'route' => 'isp.settings.index',
            'active' => ['isp.settings.index'],
        ],
        [
            'label' => 'Company & branding',
            'route' => 'isp.settings.company',
            'active' => ['isp.settings.company*', 'isp.settings.branches*', 'isp.settings.custom-fields*'],
        ],
        [
            'label' => 'Form controls',
            'route' => 'isp.settings.forms.index',
            'active' => ['isp.settings.forms.*'],
        ],
        [
            'label' => 'Payments',
            'route' => 'isp.settings.payment',
            'active' => [
                'isp.settings.payment',
                'isp.settings.payment-gateways*',
                'isp.payment-settings.*',
                'isp.settings.billing*',
            ],
        ],
        [
            'label' => 'Users & access',
            'route' => 'isp.settings.users',
            'active' => ['isp.settings.users*', 'isp.settings.roles*', 'isp.settings.security*'],
        ],
        [
            'label' => 'Notifications',
            'route' => 'isp.settings.notifications',
            'active' => ['isp.settings.notifications*', 'isp.notifications.*'],
        ],
        [
            'label' => 'Network defaults',
            'route' => 'isp.settings.router-defaults',
            'active' => ['isp.settings.router-defaults*', 'isp.settings.pppoe-defaults*', 'isp.settings.hotspot*'],
        ],
        [
            'label' => 'System',
            'route' => 'isp.settings.backups',
            'active' => [
                'isp.settings.backups*',
                'isp.settings.maintenance*',
                'isp.settings.api-integrations*',
                'isp.platform-subscription.*',
            ],
        ],
        [
            'label' => 'Audit logs',
            'route' => 'isp.logs.activity.index',
            'active' => ['isp.logs.*'],
        ],
    ],

];
