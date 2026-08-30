<?php

return [

    'scopes' => [
        'isp' => 'ISP workspace forms',
        'platform' => 'Platform admin forms',
    ],

    'forms' => [

        'customer' => [
            'label' => 'Customers',
            'scope' => 'isp',
            'description' => 'Subscriber create and edit fields (enforced at runtime).',
            'fields' => [
                'full_name' => ['label' => 'Full name', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'phone_number' => ['label' => 'Phone number', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'email' => ['label' => 'Email', 'type' => 'email', 'required' => false, 'visible' => true, 'sort_order' => 3],
                'location' => ['label' => 'Location', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 4],
                'connection_type' => ['label' => 'Connection type', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 5],
                'router_id' => ['label' => 'Router', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 6],
                'plan_id' => ['label' => 'Plan', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 7],
                'username' => ['label' => 'Username', 'type' => 'text', 'required' => false, 'visible' => true, 'sort_order' => 8],
                'password' => ['label' => 'Password', 'type' => 'text', 'required' => false, 'visible' => true, 'sort_order' => 9],
                'installation_date' => ['label' => 'Installation date', 'type' => 'date', 'required' => true, 'visible' => true, 'sort_order' => 10],
                'notes' => ['label' => 'Notes', 'type' => 'textarea', 'required' => false, 'visible' => true, 'sort_order' => 11],
            ],
        ],

        'plan' => [
            'label' => 'Service plans',
            'scope' => 'isp',
            'description' => 'Internet plan catalog fields (enforced at runtime).',
            'fields' => [
                'name' => ['label' => 'Plan name', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => false, 'visible' => true, 'sort_order' => 2],
                'package_type' => ['label' => 'Package type', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 3],
                'access_type' => ['label' => 'Access type', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 4],
                'price' => ['label' => 'Price (KES)', 'type' => 'number', 'required' => true, 'visible' => true, 'sort_order' => 5],
                'duration_days' => ['label' => 'Duration (days)', 'type' => 'number', 'required' => true, 'visible' => true, 'sort_order' => 6],
                'billing_type' => ['label' => 'Billing type', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 7],
                'data_limit_mb' => ['label' => 'Data limit (MB)', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 8],
                'time_limit_hours' => ['label' => 'Time limit (hours)', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 9],
                'device_limit' => ['label' => 'Device limit', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 10],
                'download_speed_kbps' => ['label' => 'Download speed (Kbps)', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 11],
                'upload_speed_kbps' => ['label' => 'Upload speed (Kbps)', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 12],
                'speed_profile' => ['label' => 'Speed profile name', 'type' => 'text', 'required' => false, 'visible' => true, 'sort_order' => 13],
                'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'required' => false, 'visible' => true, 'sort_order' => 14],
            ],
        ],

        'router' => [
            'label' => 'Routers',
            'scope' => 'isp',
            'description' => 'MikroTik router connection fields (enforced at runtime).',
            'fields' => [
                'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'ip_address' => ['label' => 'IP address', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'port' => ['label' => 'API port', 'type' => 'number', 'required' => true, 'visible' => true, 'sort_order' => 3, 'default' => 8728],
                'username' => ['label' => 'Username', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 4],
                'password' => ['label' => 'Password', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 5],
                'location' => ['label' => 'Location', 'type' => 'text', 'required' => false, 'visible' => true, 'sort_order' => 6],
                'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'required' => false, 'visible' => true, 'sort_order' => 7],
            ],
        ],

        'voucher' => [
            'label' => 'Vouchers',
            'scope' => 'isp',
            'description' => 'Hotspot voucher generation fields (enforced at runtime).',
            'fields' => [
                'router_id' => ['label' => 'Router', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'plan_id' => ['label' => 'Plan', 'type' => 'select', 'required' => false, 'visible' => true, 'sort_order' => 2],
                'voucher_type' => ['label' => 'Voucher type', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 3],
                'time_limit_hours' => ['label' => 'Time limit (hours)', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 4],
                'data_limit_mb' => ['label' => 'Data limit (MB)', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 5],
                'speed_profile' => ['label' => 'Speed profile', 'type' => 'text', 'required' => false, 'visible' => true, 'sort_order' => 6],
                'price' => ['label' => 'Price', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 7],
                'expires_at' => ['label' => 'Expires at', 'type' => 'date', 'required' => false, 'visible' => true, 'sort_order' => 8],
                'notes' => ['label' => 'Notes', 'type' => 'textarea', 'required' => false, 'visible' => true, 'sort_order' => 9],
            ],
        ],

        'subscription' => [
            'label' => 'Subscriptions',
            'scope' => 'isp',
            'description' => 'Customer subscription assignment fields (enforced at runtime).',
            'fields' => [
                'customer_id' => ['label' => 'Customer', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'plan_id' => ['label' => 'Plan', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'start_date' => ['label' => 'Start date', 'type' => 'date', 'required' => true, 'visible' => true, 'sort_order' => 3],
                'end_date' => ['label' => 'End date', 'type' => 'date', 'required' => true, 'visible' => true, 'sort_order' => 4],
                'status' => ['label' => 'Status', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 5],
                'auto_renew' => ['label' => 'Auto renew', 'type' => 'checkbox', 'required' => false, 'visible' => true, 'sort_order' => 6],
            ],
        ],

        'ticket' => [
            'label' => 'Support tickets',
            'scope' => 'isp',
            'description' => 'Helpdesk ticket creation fields (enforced at runtime).',
            'fields' => [
                'customer_id' => ['label' => 'Customer', 'type' => 'select', 'required' => false, 'visible' => true, 'sort_order' => 1],
                'subject' => ['label' => 'Subject', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'priority' => ['label' => 'Priority', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 3],
                'message' => ['label' => 'Message', 'type' => 'textarea', 'required' => true, 'visible' => true, 'sort_order' => 4],
            ],
        ],

        'bandwidth_profile' => [
            'label' => 'Bandwidth profiles',
            'scope' => 'isp',
            'description' => 'MikroTik queue profile fields (enforced at runtime).',
            'fields' => [
                'name' => ['label' => 'Profile name', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'download_limit' => ['label' => 'Download limit', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'upload_limit' => ['label' => 'Upload limit', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 3],
                'comment' => ['label' => 'Comment', 'type' => 'textarea', 'required' => false, 'visible' => true, 'sort_order' => 4],
            ],
        ],

        'ip_pool' => [
            'label' => 'IP pools',
            'scope' => 'isp',
            'description' => 'Address pool definition fields (enforced at runtime).',
            'fields' => [
                'name' => ['label' => 'Pool name', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'router_id' => ['label' => 'Router', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'gateway_address' => ['label' => 'Gateway', 'type' => 'text', 'required' => false, 'visible' => true, 'sort_order' => 3],
            ],
        ],

        'platform_saas_plan' => [
            'label' => 'SaaS plans',
            'scope' => 'platform',
            'description' => 'Platform subscription tier fields.',
            'fields' => [
                'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'slug' => ['label' => 'Slug', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'description' => ['label' => 'Description', 'type' => 'textarea', 'required' => false, 'visible' => true, 'sort_order' => 3],
                'max_customers' => ['label' => 'Max customers', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 4],
                'sort_order' => ['label' => 'Sort order', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 5, 'default' => 0],
                'monthly_price' => ['label' => 'Monthly price', 'type' => 'number', 'required' => true, 'visible' => true, 'sort_order' => 6],
                'setup_fee' => ['label' => 'Setup fee', 'type' => 'number', 'required' => false, 'visible' => true, 'sort_order' => 7, 'default' => 0],
                'trial_days' => ['label' => 'Trial days', 'type' => 'number', 'required' => true, 'visible' => true, 'sort_order' => 8],
                'advanced_analytics' => ['label' => 'Advanced analytics', 'type' => 'checkbox', 'required' => false, 'visible' => true, 'sort_order' => 9],
                'sms_automation' => ['label' => 'SMS automation', 'type' => 'checkbox', 'required' => false, 'visible' => true, 'sort_order' => 10],
                'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'required' => false, 'visible' => true, 'sort_order' => 11],
            ],
        ],

        'isp' => [
            'label' => 'ISP tenants',
            'scope' => 'platform',
            'description' => 'New ISP workspace provisioning fields.',
            'fields' => [
                'name' => ['label' => 'ISP name', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'company_name' => ['label' => 'Company name', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'email' => ['label' => 'Email', 'type' => 'email', 'required' => true, 'visible' => true, 'sort_order' => 3],
                'phone' => ['label' => 'Phone', 'type' => 'text', 'required' => false, 'visible' => true, 'sort_order' => 4],
                'subdomain' => ['label' => 'Subdomain', 'type' => 'text', 'required' => false, 'visible' => true, 'sort_order' => 5],
            ],
        ],

        'user' => [
            'label' => 'Platform users',
            'scope' => 'platform',
            'description' => 'Admin user account fields.',
            'fields' => [
                'name' => ['label' => 'Name', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'email' => ['label' => 'Email', 'type' => 'email', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'password' => ['label' => 'Password', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 3],
                'role' => ['label' => 'Role', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 4],
            ],
        ],

        'announcement' => [
            'label' => 'Announcements',
            'scope' => 'platform',
            'description' => 'System announcement fields.',
            'fields' => [
                'title' => ['label' => 'Title', 'type' => 'text', 'required' => true, 'visible' => true, 'sort_order' => 1],
                'body' => ['label' => 'Body', 'type' => 'textarea', 'required' => true, 'visible' => true, 'sort_order' => 2],
                'severity' => ['label' => 'Severity', 'type' => 'select', 'required' => true, 'visible' => true, 'sort_order' => 3],
                'starts_at' => ['label' => 'Starts at', 'type' => 'date', 'required' => false, 'visible' => true, 'sort_order' => 4],
                'ends_at' => ['label' => 'Ends at', 'type' => 'date', 'required' => false, 'visible' => true, 'sort_order' => 5],
                'is_active' => ['label' => 'Active', 'type' => 'checkbox', 'required' => false, 'visible' => true, 'sort_order' => 6],
            ],
        ],
    ],
];
