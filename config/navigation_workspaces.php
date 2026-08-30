<?php

/**
 * Module navigation: sidebar shows top-level modules; workspace bar shows sub-pages.
 *
 * Each module has:
 * - label, route (sidebar link), active (route patterns for sidebar highlight)
 * - workspace: horizontal tabs shown below the page header on every page in the module
 *
 * Modules with an empty workspace array are standalone (dashboard only).
 */
return [

    'isp' => [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'route' => 'isp.dashboard',
            'active' => ['isp.dashboard'],
            'workspace' => [],
        ],
        [
            'key' => 'operations',
            'label' => 'Operations',
            'route' => 'isp.customers.index',
            'active' => [
                'isp.customers.*',
                'isp.subscriptions.*',
                'isp.payments.*',
                'isp.invoices.*',
                'isp.plans.*',
                'isp.vouchers.*',
            ],
            'workspace' => [
                ['label' => 'Customers', 'route' => 'isp.customers.index', 'active' => ['isp.customers.*']],
                ['label' => 'Subscriptions', 'route' => 'isp.subscriptions.index', 'active' => ['isp.subscriptions.*']],
                ['label' => 'Invoices', 'route' => 'isp.invoices.index', 'active' => ['isp.invoices.*']],
                ['label' => 'Posted', 'route' => 'isp.payments.index', 'active' => ['isp.payments.index', 'isp.payments.show']],
                ['label' => 'Unassigned', 'route' => 'isp.payments.unposted', 'active' => ['isp.payments.unposted']],
                ['label' => 'Credits', 'route' => 'isp.payments.credits', 'active' => ['isp.payments.credits']],
                ['label' => 'Failed', 'route' => 'isp.payments.failed', 'active' => ['isp.payments.failed']],
                ['label' => 'Webhooks', 'route' => 'isp.payments.transaction-logs', 'active' => ['isp.payments.transaction-logs', 'isp.payments.transaction-log.*']],
                ['label' => 'Plans & vouchers', 'route' => 'isp.plans.index', 'active' => ['isp.plans.*', 'isp.vouchers.*']],
            ],
        ],
        [
            'key' => 'network',
            'label' => 'Network',
            'route' => 'isp.routers.index',
            'active' => [
                'isp.provisioning-hub.*',
                'isp.router-migration.*',
                'isp.active-users.*',
                'isp.sessions.*',
                'isp.data-usage.*',
                'isp.communications.*',
                'isp.routers.*',
                'isp.hotspot-servers.*',
                'isp.user-connections.*',
                'isp.ip-pools.*',
                'isp.bandwidth-profiles.*',
                'isp.outages.*',
                'isp.inventory.*',
            ],
            'workspace' => [
                ['label' => 'Routers', 'route' => 'isp.routers.index', 'active' => ['isp.routers.*']],
                ['label' => 'Hotspot servers', 'route' => 'isp.hotspot-servers.index', 'active' => ['isp.hotspot-servers.*']],
                ['label' => 'IP pools', 'route' => 'isp.ip-pools.index', 'active' => ['isp.ip-pools.*']],
                ['label' => 'Bandwidth profiles', 'route' => 'isp.bandwidth-profiles.index', 'active' => ['isp.bandwidth-profiles.*']],
                ['label' => 'PPPoE & connections', 'route' => 'isp.user-connections.index', 'active' => ['isp.user-connections.*']],
                ['label' => 'Provisioning hub', 'route' => 'isp.provisioning-hub.index', 'active' => ['isp.provisioning-hub.*']],
                ['label' => 'Router migration', 'route' => 'isp.router-migration.index', 'active' => ['isp.router-migration.*']],
                ['label' => 'Active users', 'route' => 'isp.active-users.index', 'active' => ['isp.active-users.*']],
                ['label' => 'Live sessions', 'route' => 'isp.sessions.index', 'active' => ['isp.sessions.*']],
                ['label' => 'Data usage', 'route' => 'isp.data-usage.index', 'active' => ['isp.data-usage.*']],
                ['label' => 'Communications', 'route' => 'isp.communications.index', 'active' => ['isp.communications.*']],
                ['label' => 'Outages', 'route' => 'isp.outages.index', 'active' => ['isp.outages.*']],
                ['label' => 'Inventory', 'route' => 'isp.inventory.index', 'active' => ['isp.inventory.*']],
            ],
        ],
        [
            'key' => 'support',
            'label' => 'Support',
            'route' => 'isp.tickets.index',
            'active' => ['isp.tickets.*', 'isp.helpdesk.*'],
            'workspace' => [
                ['label' => 'Support tickets', 'route' => 'isp.tickets.index', 'active' => ['isp.tickets.*']],
                ['label' => 'PrimeBilling helpdesk', 'route' => 'isp.helpdesk.index', 'active' => ['isp.helpdesk.*']],
            ],
        ],
        [
            'key' => 'analytics',
            'label' => 'Analytics',
            'route' => 'isp.reports.index',
            'active' => ['isp.reports.*'],
            'workspace' => [
                [
                    'label' => 'Overview',
                    'route' => 'isp.reports.index',
                    'active' => ['isp.reports.*'],
                    'query' => ['focus' => null],
                ],
                [
                    'label' => 'Revenue',
                    'route' => 'isp.reports.index',
                    'params' => ['focus' => 'revenue-per-plan'],
                    'active' => ['isp.reports.*'],
                    'query' => ['focus' => 'revenue-per-plan'],
                ],
                [
                    'label' => 'Collections',
                    'route' => 'isp.reports.index',
                    'params' => ['focus' => 'monthly-collections'],
                    'active' => ['isp.reports.*'],
                    'query' => ['focus' => 'monthly-collections'],
                ],
                [
                    'label' => 'Customer growth',
                    'route' => 'isp.reports.index',
                    'params' => ['focus' => 'customer-growth'],
                    'active' => ['isp.reports.*'],
                    'query' => ['focus' => 'customer-growth'],
                ],
            ],
        ],
        [
            'key' => 'settings',
            'label' => 'Settings',
            'route' => 'isp.settings.index',
            'active' => [
                'isp.settings.*',
                'isp.payment-settings.*',
                'isp.logs.*',
                'isp.notifications.*',
                'isp.platform-subscription.*',
            ],
            'workspace' => [
                ['label' => 'Operations hub', 'route' => 'isp.settings.index', 'active' => ['isp.settings.index']],
                [
                    'label' => 'Company & branding',
                    'route' => 'isp.settings.company',
                    'active' => ['isp.settings.company*', 'isp.settings.branches*', 'isp.settings.custom-fields*'],
                ],
                ['label' => 'Form controls', 'route' => 'isp.settings.forms.index', 'active' => ['isp.settings.forms.*']],
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
                ['label' => 'Audit logs', 'route' => 'isp.logs.activity.index', 'active' => ['isp.logs.*']],
            ],
        ],
    ],

    'admin' => [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'active' => ['admin.dashboard'],
            'workspace' => [],
        ],
        [
            'key' => 'platform',
            'label' => 'Platform',
            'route' => 'admin.isps.index',
            'active' => [
                'admin.isps.*',
                'admin.finance.*',
                'admin.helpdesk.*',
                'admin.users.*',
                'admin.platform-saas-plans.*',
                'admin.network.*',
                'admin.system-health.*',
                'admin.audit.activity',
                'admin.audit.activity.show',
                'admin.announcements.*',
                'admin.intelligence.*',
                'admin.marketing.*',
                'admin.communications.*',
            ],
            'workspace' => [
                ['label' => 'All ISPs', 'route' => 'admin.isps.index', 'active' => ['admin.isps.*']],
                ['label' => 'SaaS plans', 'route' => 'admin.platform-saas-plans.index', 'active' => ['admin.platform-saas-plans.*']],
                ['label' => 'Finance', 'route' => 'admin.finance.index', 'active' => ['admin.finance.*']],
                ['label' => 'Operators', 'route' => 'admin.users.index', 'active' => ['admin.users.*']],
                ['label' => 'ISP helpdesk', 'route' => 'admin.helpdesk.index', 'active' => ['admin.helpdesk.*']],
                ['label' => 'Network NOC', 'route' => 'admin.network.index', 'active' => ['admin.network.*']],
                ['label' => 'Fleet health', 'route' => 'admin.system-health.index', 'active' => ['admin.system-health.*']],
                ['label' => 'SMS control', 'route' => 'admin.communications.index', 'active' => ['admin.communications.*']],
                ['label' => 'Marketing', 'route' => 'admin.marketing.index', 'active' => ['admin.marketing.*']],
                ['label' => 'Announcements', 'route' => 'admin.announcements.index', 'active' => ['admin.announcements.*']],
                ['label' => 'Intelligence', 'route' => 'admin.intelligence.index', 'active' => ['admin.intelligence.*']],
                ['label' => 'Global audit', 'route' => 'admin.audit.activity', 'active' => ['admin.audit.activity', 'admin.audit.activity.show']],
            ],
        ],
        [
            'key' => 'settings',
            'label' => 'Settings',
            'route' => 'admin.settings.index',
            'active' => [
                'admin.settings.*',
                'admin.integrations.*',
                'admin.audit.security',
                'admin.promo-codes.*',
            ],
            'workspace' => [
                ['label' => 'General', 'route' => 'admin.settings.index', 'active' => ['admin.settings.index']],
                ['label' => 'Form controls', 'route' => 'admin.settings.forms.index', 'active' => ['admin.settings.forms.*']],
                ['label' => 'Integrations', 'route' => 'admin.integrations.index', 'active' => ['admin.integrations.*']],
                ['label' => 'Promo codes', 'route' => 'admin.promo-codes.index', 'active' => ['admin.promo-codes.*']],
                ['label' => 'Security', 'route' => 'admin.audit.security', 'active' => ['admin.audit.security']],
            ],
        ],
    ],

    'technician' => [
        [
            'key' => 'field',
            'label' => 'Field',
            'route' => 'isp.technician.inventory.index',
            'active' => ['isp.technician.inventory.*'],
            'workspace' => [
                ['label' => 'My devices', 'route' => 'isp.technician.inventory.index', 'active' => ['isp.technician.inventory.index', 'isp.technician.inventory.show']],
                ['label' => 'Scan device', 'route' => 'isp.technician.inventory.scan', 'active' => ['isp.technician.inventory.scan']],
            ],
        ],
    ],

];
