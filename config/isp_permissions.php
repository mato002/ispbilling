<?php

/**
 * ISP workspace permission catalog and default grants per role.
 * Overrides are stored per tenant in isp.settings.role_permissions[role][permission].
 */
return [

    'roles' => [
        'isp_admin' => [
            'label' => 'ISP administrator',
            'description' => 'Full workspace control including settings, billing configuration, and user management.',
        ],
        'isp_staff' => [
            'label' => 'Staff operator',
            'description' => 'Day-to-day operations with configurable access to customers, network, and finance modules.',
        ],
        'isp_technician' => [
            'label' => 'Field technician',
            'description' => 'Mobile field workflow for installing and returning customer equipment.',
        ],
    ],

    'groups' => [
        'customers' => [
            'label' => 'Customers & subscriptions',
            'permissions' => [
                'customers.view' => 'View customers and profiles',
                'customers.manage' => 'Create, edit, and suspend customers',
                'subscriptions.manage' => 'Manage subscriptions and plan changes',
            ],
        ],
        'network' => [
            'label' => 'Network operations',
            'permissions' => [
                'routers.view' => 'View routers and sessions',
                'routers.manage' => 'Configure routers and run network actions',
                'hotspot.manage' => 'Manage hotspot servers and vouchers',
                'inventory.manage' => 'Manage equipment inventory and assignments',
            ],
        ],
        'technician' => [
            'label' => 'Field inventory',
            'permissions' => [
                'inventory.technician' => 'View assigned devices and complete installations',
                'inventory.scan' => 'Scan device QR codes in the field',
            ],
        ],
        'finance' => [
            'label' => 'Billing & payments',
            'permissions' => [
                'invoices.view' => 'View invoices',
                'invoices.manage' => 'Create and adjust invoices',
                'payments.collect' => 'Initiate and record payments',
                'reports.view' => 'View analytics and reports',
            ],
        ],
        'support' => [
            'label' => 'Support',
            'permissions' => [
                'tickets.manage' => 'Manage support tickets',
                'helpdesk.access' => 'Access PrimeBilling helpdesk',
            ],
        ],
        'settings' => [
            'label' => 'Workspace settings',
            'permissions' => [
                'settings.company' => 'Edit company profile and branding',
                'settings.billing' => 'Edit tax and invoicing policy',
                'settings.payments' => 'Configure payment methods and gateways',
                'settings.users' => 'Manage workspace users',
                'settings.roles' => 'Configure role permissions',
                'settings.forms' => 'Configure form controls',
                'settings.security' => 'Edit security and IP allowlist',
                'logs.view' => 'View audit logs',
                'notifications.view' => 'View in-app notifications',
                'platform_subscription.manage' => 'Manage PrimeBilling SaaS subscription',
            ],
        ],
    ],

    /*
    | Default grants when no tenant override exists.
    | ISP administrators always receive every permission at runtime.
    */
    'defaults' => [
        'isp_staff' => [
            'customers.view' => true,
            'customers.manage' => true,
            'subscriptions.manage' => false,
            'routers.view' => true,
            'routers.manage' => false,
            'hotspot.manage' => false,
            'inventory.manage' => false,
            'invoices.view' => true,
            'invoices.manage' => false,
            'payments.collect' => true,
            'reports.view' => true,
            'tickets.manage' => true,
            'helpdesk.access' => true,
            'settings.company' => false,
            'settings.billing' => false,
            'settings.payments' => false,
            'settings.users' => false,
            'settings.roles' => false,
            'settings.forms' => false,
            'settings.security' => false,
            'logs.view' => true,
            'notifications.view' => true,
            'platform_subscription.manage' => false,
        ],
        'isp_technician' => [
            'inventory.technician' => true,
            'inventory.scan' => true,
        ],
    ],

];
