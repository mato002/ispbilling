<?php

return [

    'results_limit' => 24,
    'recent_limit' => 12,
    'favorites_limit' => 12,

    'keyword_aliases' => [
        'customer' => ['customers', 'subscriber', 'subscribers', 'client'],
        'invoice' => ['invoices', 'billing', 'bill'],
        'payment' => ['payments', 'mpesa', 'collection', 'collections'],
        'router' => ['routers', 'mikrotik', 'network', 'edge'],
        'plan' => ['plans', 'package', 'packages', 'voucher', 'vouchers'],
        'session' => ['sessions', 'online', 'active'],
        'usage' => ['bandwidth', 'traffic', 'data', 'monitoring'],
        'report' => ['reports', 'analytics', 'revenue'],
        'setting' => ['settings', 'configuration', 'config', 'forms'],
        'ticket' => ['tickets', 'support', 'helpdesk'],
        'isp' => ['isps', 'tenant', 'tenants', 'workspace'],
        'user' => ['users', 'operator', 'operators', 'staff'],
        'audit' => ['logs', 'activity', 'security'],
        'hotspot' => ['captive', 'wifi'],
        'subscription' => ['subscriptions', 'renewal'],
    ],

    'category_labels' => [
        'navigation' => 'Navigation',
        'operations' => 'Operations',
        'network' => 'Network',
        'analytics' => 'Analytics',
        'settings' => 'Settings',
        'platform' => 'Platform',
    ],

];
