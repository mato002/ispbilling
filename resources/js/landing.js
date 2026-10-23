import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('landingHero', () => ({
    active: 0,
    timer: null,
    slides: [
        {
            lead: 'Run billing, network access, and', emphasis: 'customer operations', end: 'from one platform',
            description: 'Connect M-Pesa collections, MikroTik provisioning, subscriber management, and field operations in one clear workspace — built for growing African ISPs.',
            badges: ['MikroTik-native', 'M-Pesa STK Push', 'Hotspot & PPPoE', 'Multi-tenant SaaS'],
        },
        {
            lead: 'Turn every payment into', emphasis: 'instant access', end: 'for your subscribers',
            description: 'Reconcile M-Pesa payments, renew subscriptions, and restore network access automatically while your team stays focused on service.',
            badges: ['Automatic renewals', 'STK Push', 'Live reconciliation', 'SMS updates'],
        },
        {
            lead: 'See your entire network', emphasis: 'at a glance', end: 'and act with confidence',
            description: 'Bring routers, sessions, collections, customer support, and field operations together with the visibility to grow reliably.',
            badges: ['Live router health', 'Subscriber insights', 'Field operations', 'One dashboard'],
        },
    ],
    init() { this.resume(); },
    destroy() { this.pause(); },
    goTo(index) { this.active = index; this.restart(); },
    next() { this.active = (this.active + 1) % this.slides.length; },
    previous() { this.active = (this.active + this.slides.length - 1) % this.slides.length; },
    pause() { if (this.timer) window.clearInterval(this.timer); this.timer = null; },
    resume() {
        this.pause();
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.timer = window.setInterval(() => this.next(), 6500);
        }
    },
    restart() { this.resume(); },
}));

Alpine.data('landingPricing', (config = {}) => ({
    pppoeUsers: config.defaultPppoe ?? 30,
    hotspotRevenue: config.defaultHotspot ?? 5000,
    perSubscriber: config.perSubscriber ?? 25,
    hotspotPercent: config.hotspotPercent ?? 3,
    minimum: config.minimum ?? 500,
    activation: config.activation ?? 500,

    get pppoeCost() {
        return this.pppoeUsers * this.perSubscriber;
    },

    get hotspotCost() {
        return Math.round(this.hotspotRevenue * (this.hotspotPercent / 100));
    },

    get subtotal() {
        return this.pppoeCost + this.hotspotCost;
    },

    get monthly() {
        return Math.max(this.minimum, this.subtotal);
    },

    format(n) {
        return new Intl.NumberFormat('en-KE', { maximumFractionDigits: 0 }).format(n);
    },

    updateRangeBg(el) {
        if (!el) return;
        const min = Number(el.min);
        const max = Number(el.max);
        const val = Number(el.value);
        const pct = ((val - min) / (max - min)) * 100;
        el.style.background = `linear-gradient(90deg, #2563eb ${pct}%, #e2e8f0 ${pct}%)`;
    },

    init() {
        this.$nextTick(() => {
            this.$root.querySelectorAll('.pb-range').forEach((el) => this.updateRangeBg(el));
        });
    },
}));

Alpine.data('landingFeatures', () => ({
    active: 'billing',
    tabs: {
        billing: {
            title: 'Bill customers automatically',
            subtitle: 'Recurring invoices, M-Pesa STK Push, and instant access restoration when payment lands.',
            bullets: [
                'Automated invoicing with due-date tracking and grace periods',
                'M-Pesa Paybill, Till, and STK Push with real-time reconciliation',
                'Auto suspend on overdue accounts and reconnect on payment',
                'Customer self-service portal for renewals, receipts, and usage',
            ],
        },
        network: {
            title: 'Provision and control your MikroTik fleet',
            subtitle: 'PPPoE, hotspot, bandwidth profiles, and router automation from one control plane.',
            bullets: [
                'Hotspot captive portals with voucher and micropayment flows',
                'PPPoE subscriber management with bandwidth profile sync',
                'Provisioning hub for MAC binding, queues, and session rebuild',
                'Router migration, backups, fleet health, and IP pool management',
            ],
        },
        support: {
            title: 'Support subscribers without the chaos',
            subtitle: 'Ticketing, SMS campaigns, outage notices, and communication history in one CRM layer.',
            bullets: [
                'Helpdesk tickets linked to customer accounts and service history',
                'SMS groups, campaigns, and automated payment reminders',
                'Outage logging with subscriber compensation workflows',
                'Activity and router audit logs for accountability',
            ],
        },
        operations: {
            title: 'Run field and inventory operations',
            subtitle: 'Track CPE, assign gear to technicians, and close the loop from warehouse to installation.',
            bullets: [
                'Inventory with QR scanning and category thresholds',
                'Technician device assignments and installation binding',
                'Data usage monitoring and bandwidth cap enforcement',
                'Reports for revenue, collections, growth, and plan performance',
            ],
        },
    },
}));

document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            const expanded = menuBtn.getAttribute('aria-expanded') === 'true';
            menuBtn.setAttribute('aria-expanded', String(!expanded));
        });

        mobileMenu.querySelectorAll('a[href^="#"]').forEach((link) => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                menuBtn.setAttribute('aria-expanded', 'false');
            });
        });
    }

    const revealEls = document.querySelectorAll('.pb-reveal');
    if (revealEls.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
        );
        revealEls.forEach((el) => observer.observe(el));
    } else {
        revealEls.forEach((el) => el.classList.add('is-visible'));
    }
});

Alpine.start();
