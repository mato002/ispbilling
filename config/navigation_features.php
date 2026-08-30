<?php

/**
 * Searchable navigation catalog for Cmd+K feature discovery.
 * Mirrors sidebar structure; scoped by user role at runtime.
 */
return [

    'admin' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'section' => 'Platform', 'category' => 'platform', 'keywords' => ['home', 'overview']],
        ['label' => 'All ISPs', 'route' => 'admin.isps.index', 'section' => 'Platform', 'category' => 'platform', 'keywords' => ['tenants', 'workspaces']],
        ['label' => 'Finance', 'route' => 'admin.finance.index', 'section' => 'Platform', 'category' => 'analytics', 'keywords' => ['revenue', 'payouts']],
        ['label' => 'ISP helpdesk', 'route' => 'admin.helpdesk.index', 'section' => 'Platform', 'category' => 'operations', 'keywords' => ['tickets', 'support']],
        ['label' => 'Operators', 'route' => 'admin.users.index', 'section' => 'Platform', 'category' => 'platform', 'keywords' => ['users', 'staff']],
        ['label' => 'SaaS plans', 'route' => 'admin.platform-saas-plans.index', 'section' => 'Platform', 'category' => 'settings', 'keywords' => ['pricing', 'tiers']],
        ['label' => 'System settings', 'route' => 'admin.settings.index', 'section' => 'Settings', 'category' => 'settings', 'keywords' => ['configuration']],
        ['label' => 'Form controls', 'route' => 'admin.settings.forms.index', 'section' => 'Settings', 'category' => 'settings', 'keywords' => ['forms', 'fields']],
        ['label' => 'Integrations', 'route' => 'admin.integrations.index', 'section' => 'Settings', 'category' => 'settings', 'keywords' => ['api', 'vault']],
        ['label' => 'Hotspot portal', 'route' => 'isp.settings.hotspot', 'section' => 'Settings', 'category' => 'settings', 'keywords' => ['hotspot', 'captive', 'portal']],
        ['label' => 'Security audit', 'route' => 'admin.audit.security', 'section' => 'Settings', 'category' => 'platform', 'keywords' => ['security']],
        ['label' => 'Promo codes', 'route' => 'admin.promo-codes.index', 'section' => 'Settings', 'category' => 'platform', 'keywords' => ['discount', 'coupon']],
        ['label' => 'Network NOC', 'route' => 'admin.network.index', 'section' => 'Platform', 'category' => 'network', 'keywords' => ['routers', 'fleet']],
        ['label' => 'Fleet health', 'route' => 'admin.system-health.index', 'section' => 'Platform', 'category' => 'network', 'keywords' => ['health', 'status']],
        ['label' => 'Global audit', 'route' => 'admin.audit.activity', 'section' => 'Platform', 'category' => 'platform', 'keywords' => ['logs', 'activity']],
        ['label' => 'Announcements', 'route' => 'admin.announcements.index', 'section' => 'Platform', 'category' => 'platform', 'keywords' => ['broadcast', 'notice']],
        ['label' => 'Intelligence', 'route' => 'admin.intelligence.index', 'section' => 'Platform', 'category' => 'analytics', 'keywords' => ['insights']],
        ['label' => 'Marketing', 'route' => 'admin.marketing.index', 'section' => 'Platform', 'category' => 'platform', 'keywords' => ['campaigns']],
        ['label' => 'SMS control', 'route' => 'admin.communications.index', 'section' => 'Platform', 'category' => 'platform', 'keywords' => ['sms', 'communications', 'messaging', 'wallet']],
    ],

    'isp' => [
        ['label' => 'Dashboard', 'route' => 'isp.dashboard', 'section' => 'Main', 'category' => 'navigation', 'keywords' => ['home', 'overview']],
        ['label' => 'Customers', 'route' => 'isp.customers.index', 'section' => 'Main', 'category' => 'operations', 'keywords' => ['subscribers']],
        ['label' => 'Add customer', 'route' => 'isp.customers.create', 'section' => 'Main', 'category' => 'operations', 'keywords' => ['new subscriber', 'onboard']],
        ['label' => 'Subscriptions', 'route' => 'isp.subscriptions.index', 'section' => 'Main', 'category' => 'operations', 'keywords' => ['renewals']],
        ['label' => 'Payments', 'route' => 'isp.payments.index', 'section' => 'Main', 'category' => 'operations', 'keywords' => ['mpesa', 'collections']],
        ['label' => 'Invoices', 'route' => 'isp.invoices.index', 'section' => 'Main', 'category' => 'operations', 'keywords' => ['billing']],
        ['label' => 'Plans & vouchers', 'route' => 'isp.plans.index', 'section' => 'Main', 'category' => 'operations', 'keywords' => ['packages']],
        ['label' => 'Provisioning hub', 'route' => 'isp.provisioning-hub.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['mac', 'binding', 'queue', 'provisioning', 'control plane']],
        ['label' => 'Router migration', 'route' => 'isp.router-migration.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['cutover', 'migrate', 'transfer', 'fleet']],
        ['label' => 'Active users', 'route' => 'isp.active-users.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['online']],
        ['label' => 'Live sessions', 'route' => 'isp.sessions.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['sessions']],
        ['label' => 'Data usage', 'route' => 'isp.data-usage.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['bandwidth', 'traffic', 'usage', 'monitoring', 'data']],
        ['label' => 'Communications', 'route' => 'isp.communications.index', 'section' => 'Support', 'category' => 'operations', 'keywords' => ['sms', 'email', 'broadcast', 'campaign', 'messaging', 'notify']],
        ['label' => 'Routers', 'route' => 'isp.routers.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['mikrotik']],
        ['label' => 'Hotspot servers', 'route' => 'isp.hotspot-servers.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['captive portal']],
        ['label' => 'PPPoE & connections', 'route' => 'isp.user-connections.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['connections']],
        ['label' => 'IP pools', 'route' => 'isp.ip-pools.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['addresses']],
        ['label' => 'Bandwidth profiles', 'route' => 'isp.bandwidth-profiles.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['queues', 'speed']],
        ['label' => 'Outage compensation', 'route' => 'isp.outages.index', 'section' => 'Network', 'category' => 'network', 'keywords' => ['downtime', 'outage', 'compensation', 'credits']],
        ['label' => 'Inventory', 'route' => 'isp.inventory.index', 'section' => 'Network', 'category' => 'operations', 'keywords' => ['stock', 'equipment', 'cpe', 'devices', 'warehouse']],
        ['label' => 'Scan device', 'route' => 'isp.inventory.scan', 'section' => 'Network', 'category' => 'operations', 'keywords' => ['qr', 'barcode', 'scan', 'camera']],
        ['label' => 'Support tickets', 'route' => 'isp.tickets.index', 'section' => 'Support', 'category' => 'operations', 'keywords' => ['helpdesk']],
        ['label' => 'Vouchers', 'route' => 'isp.vouchers.index', 'section' => 'Support', 'category' => 'operations', 'keywords' => ['hotspot codes']],
        ['label' => 'PrimeBilling helpdesk', 'route' => 'isp.helpdesk.index', 'section' => 'Support', 'category' => 'operations', 'keywords' => ['platform support']],
        ['label' => 'Reports', 'route' => 'isp.reports.index', 'section' => 'Analytics', 'category' => 'analytics', 'keywords' => ['analytics']],
        ['label' => 'Revenue report', 'route' => 'isp.reports.index', 'section' => 'Analytics', 'category' => 'analytics', 'keywords' => ['revenue'], 'params' => ['focus' => 'revenue-per-plan'], 'hash' => 'revenue-per-plan'],
        ['label' => 'Collections report', 'route' => 'isp.reports.index', 'section' => 'Analytics', 'category' => 'analytics', 'keywords' => ['collections'], 'params' => ['focus' => 'monthly-collections'], 'hash' => 'monthly-collections'],
        ['label' => 'Customer growth', 'route' => 'isp.reports.index', 'section' => 'Analytics', 'category' => 'analytics', 'keywords' => ['growth'], 'params' => ['focus' => 'customer-growth'], 'hash' => 'customer-growth'],
        ['label' => 'Settings', 'route' => 'isp.settings.index', 'section' => 'System', 'category' => 'settings', 'keywords' => ['configuration']],
        ['label' => 'Form controls', 'route' => 'isp.settings.forms.index', 'section' => 'System', 'category' => 'settings', 'keywords' => ['forms', 'fields']],
        ['label' => 'Audit logs', 'route' => 'isp.logs.activity.index', 'section' => 'System', 'category' => 'settings', 'keywords' => ['activity']],
        ['label' => 'Notifications', 'route' => 'isp.notifications.index', 'section' => 'System', 'category' => 'settings', 'keywords' => ['alerts']],
        ['label' => 'PrimeBilling subscription', 'route' => 'isp.platform-subscription.index', 'section' => 'System', 'category' => 'settings', 'keywords' => ['saas', 'platform bill']],
    ],

    'technician' => [
        ['label' => 'My devices', 'route' => 'isp.technician.inventory.index', 'section' => 'Field', 'category' => 'operations', 'keywords' => ['assigned', 'equipment', 'install']],
        ['label' => 'Scan device', 'route' => 'isp.technician.inventory.scan', 'section' => 'Field', 'category' => 'operations', 'keywords' => ['qr', 'barcode', 'camera']],
    ],

];
