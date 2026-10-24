import './bootstrap';

import '@hotwired/turbo';
import './pb-modal-forms';
import './pb-discovery';
import Alpine from 'alpinejs';

const PB_MAIN_FRAME = 'pb-main';
const PB_FRAME_NAV_BLOCKLIST = ['/profile', '/logout', '/saas-onboarding'];

function syncCsrfFromMeta() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token && window.axios) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
}

document.addEventListener('turbo:before-render', (event) => {
    const newBody = event.detail?.newBody;
    if (!newBody || !window.Alpine) {
        return;
    }
    // Init the whole shell so sidebar / overlay / main all bind to $store (not only <main>).
    const shell = newBody.querySelector('#pb-admin-shell');
    if (shell) {
        window.Alpine.initTree(shell);

        return;
    }
    const main = newBody.querySelector('main');
    if (main) {
        window.Alpine.initTree(main);
    }
});

document.addEventListener('turbo:load', () => {
    syncCsrfFromMeta();
    processPbFlashPayload();
    syncPbShellFromFrame();
    initClickableTableRows();
    processOnboardingFeedback(document);
});

document.addEventListener('turbo:frame-render', (event) => {
    if (!['pb-saas-onboarding-frame', 'pb-saas-onboarding-page-frame'].includes(event.target?.id)) {
        return;
    }

    processOnboardingFeedback(event.target);
});

document.addEventListener('turbo:frame-render', (event) => {
    if (event.target?.id !== PB_MAIN_FRAME) {
        return;
    }

    syncCsrfFromMeta();
    processPbFlashPayload();
    syncPbShellFromFrame();

    if (window.Alpine) {
        window.Alpine.initTree(event.target);
    }

    initClickableTableRows();
});

document.addEventListener('DOMContentLoaded', () => {
    syncCsrfFromMeta();
    processPbFlashPayload();
    syncPbShellFromFrame();
    initClickableTableRows();
    processOnboardingFeedback(document);
});

function processOnboardingFeedback(root) {
    const feedback = root.querySelector?.('[data-pb-onboarding-alert]');
    if (! feedback) {
        return;
    }

    if (window.Swal) {
        window.Swal.fire({
            icon: feedback.dataset.icon || 'info',
            title: feedback.dataset.title || 'Workspace setup',
            text: feedback.dataset.message || '',
            confirmButtonColor: '#4f46e5',
        });
    }

    feedback.remove();
}

function shouldIgnoreTableRowNav(target) {
    return target.closest('a, button, input, select, textarea, form, label, [data-no-row-nav]');
}

function navigateTableRow(row) {
    const href = row.dataset.rowHref;
    if (! href) {
        return;
    }

    if (row.dataset.rowTarget === '_blank') {
        window.open(href, '_blank', 'noopener');
        return;
    }

    if (window.Turbo) {
        window.Turbo.visit(href, { frame: PB_MAIN_FRAME });
        return;
    }

    window.location.assign(href);
}

function onClickableTableRowClick(event) {
    if (shouldIgnoreTableRowNav(event.target)) {
        return;
    }

    const row = event.target.closest('tr[data-row-href]');
    if (! row) {
        return;
    }

    navigateTableRow(row);
}

function onClickableTableRowKeydown(event) {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    if (shouldIgnoreTableRowNav(event.target)) {
        return;
    }

    const row = event.target.closest('tr[data-row-href]');
    if (! row) {
        return;
    }

    event.preventDefault();
    navigateTableRow(row);
}

function initClickableTableRows() {
    document.querySelectorAll('.isp-table-wrap').forEach((wrapper) => {
        if (wrapper.dataset.rowClickBound === '1') {
            return;
        }

        wrapper.dataset.rowClickBound = '1';
        wrapper.addEventListener('click', onClickableTableRowClick);
        wrapper.addEventListener('keydown', onClickableTableRowKeydown);
    });
}

function shouldTargetMainFrame(link) {
    if (!(link instanceof HTMLAnchorElement) || ! link.href) {
        return false;
    }

    if (link.hasAttribute('data-turbo-frame') || link.getAttribute('data-turbo') === 'false') {
        return false;
    }

    if (link.getAttribute('target') === '_blank' || link.hasAttribute('download')) {
        return false;
    }

    if (link.closest('#pb-form-modal')) {
        return false;
    }

    if (! link.closest('.pb-sidebar, #pb-admin-topbar, turbo-frame#pb-main')) {
        return false;
    }

    try {
        const url = new URL(link.href, window.location.origin);

        if (url.origin !== window.location.origin) {
            return false;
        }

        const path = url.pathname.toLowerCase();

        if (PB_FRAME_NAV_BLOCKLIST.some((fragment) => path.includes(fragment))) {
            return false;
        }

        if (/\/(create|edit)(\/|$)/.test(path) && ! link.hasAttribute('data-no-modal')) {
            return false;
        }
    } catch {
        return false;
    }

    return true;
}

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');

    if (! shouldTargetMainFrame(link)) {
        return;
    }

    link.dataset.turboFrame = PB_MAIN_FRAME;
}, true);

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function buildMpesaVerificationModalHtml(modal) {
    let html = '<div class="text-left text-sm leading-relaxed text-slate-600 dark:text-slate-300 space-y-3">';
    html += `<p class="text-slate-600 dark:text-slate-300">${escapeHtml(modal.message || '')}</p>`;

    const details = modal.developer_details;
    if (details) {
        html += '<details class="mt-4 rounded-xl border border-slate-200 bg-slate-50/90 p-3 dark:border-slate-600 dark:bg-slate-800/60">';
        html += '<summary class="cursor-pointer select-none text-xs font-semibold text-slate-700 dark:text-slate-200">🛠️ Developer Debugging Logs</summary>';
        html += '<div class="mt-3 space-y-2.5 text-xs leading-relaxed text-slate-600 dark:text-slate-400">';

        if (details.summary) {
            html += `<p>${escapeHtml(details.summary)}</p>`;
        }

        if (details.steps?.length) {
            html += '<ol class="list-decimal list-inside space-y-1.5 pl-0.5">';
            details.steps.forEach((step) => {
                html += `<li>${escapeHtml(step)}</li>`;
            });
            html += '</ol>';
        }

        if (details.callback_url) {
            html += `<p class="mt-2 rounded-lg bg-white/80 px-2 py-1.5 font-mono text-[11px] text-slate-500 dark:bg-slate-900/80 dark:text-slate-400">${escapeHtml(details.callback_url)}</p>`;
        }

        html += '</div></details>';
    }

    html += '</div>';

    return html;
}

function showFlashModal(modal) {
    if (!modal || modal.type !== 'mpesa_verification_pending') {
        return false;
    }

    window.Swal.fire({
        icon: 'info',
        title: modal.title || 'Payment Verification Pending',
        html: buildMpesaVerificationModalHtml(modal),
        confirmButtonText: 'Got it',
        confirmButtonColor: '#4f46e5',
        customClass: {
            popup: 'pb-mpesa-verify-swal',
            htmlContainer: 'pb-mpesa-verify-swal__body',
            confirmButton: 'pb-mpesa-verify-swal__confirm',
        },
    });

    return true;
}

function processPbFlashPayload() {
    const el = document.getElementById('pb-flash-payload');
    if (!el) {
        return;
    }
    if (!window.Swal) {
        let payload = {};
        try {
            payload = JSON.parse(el.textContent || '{}');
        } catch {
            el.remove();

            return;
        }
        el.remove();
        if (payload.flash_modal?.message) {
            window.alert(payload.flash_modal.message);
        } else if (payload.validation_errors?.length) {
            window.alert(payload.validation_errors.join('\n'));
        } else if (payload.error) {
            window.alert(payload.error);
        }

        return;
    }
    let payload = {};
    try {
        payload = JSON.parse(el.textContent || '{}');
    } catch {
        el.remove();

        return;
    }
    el.remove();

    if (payload.flash_modal && showFlashModal(payload.flash_modal)) {
        return;
    }

    if (payload.success) {
        window.Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: payload.success,
            confirmButtonColor: '#10b981',
            timer: 3000,
            timerProgressBar: true,
        });
    }
    if (payload.error) {
        window.Swal.fire({
            icon: 'error',
            title: 'Something went wrong',
            text: payload.error,
            confirmButtonColor: '#4f46e5',
            confirmButtonText: 'Got it',
        });
    }
    if (payload.warning) {
        window.Swal.fire({
            icon: 'warning',
            title: 'Warning!',
            text: payload.warning,
            confirmButtonColor: '#f59e0b',
        });
    }
    if (payload.info) {
        window.Swal.fire({
            icon: 'info',
            title: 'Information',
            text: payload.info,
            confirmButtonColor: '#4f46e5',
        });
    }
    if (payload.validation_errors?.length) {
        window.Swal.fire({
            icon: 'error',
            title: 'Please fix the following',
            html: '<ul class="mt-2 list-inside list-disc space-y-1 text-left text-sm text-slate-600 dark:text-slate-300">'
                + payload.validation_errors.map((msg) => `<li>${escapeHtml(msg)}</li>`).join('')
                + '</ul>',
            confirmButtonColor: '#4f46e5',
            confirmButtonText: 'Got it',
        });
    }
}

Alpine.data('pbNavGroup', (startOpen = false) => ({
    open: Boolean(startOpen),

    toggle() {
        this.open = ! this.open;
    },

    syncFromSidebar() {
        const hasActiveChild = Boolean(this.$el.querySelector('.pb-subnav-item.is-active'));
        const trigger = this.$el.querySelector('[data-nav-group-trigger]');

        if (hasActiveChild) {
            this.open = true;
            trigger?.classList.add('is-active');
        } else if (trigger && ! trigger.classList.contains('is-active')) {
            this.open = false;
        }
    },

    init() {
        this.syncFromSidebar();
        window.addEventListener('pb:sidebar-sync', () => this.syncFromSidebar());
    },
}));

window.Alpine = Alpine;

// Create a global store for sidebar state
const pbSidebarStore = {
    collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    mobileOpen: false,

    toggle() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('sidebarCollapsed', this.collapsed);
        document.documentElement.classList.toggle('pb-sidebar-collapsed', this.collapsed);
        window.dispatchEvent(new Event('sidebarToggle'));
    },

    openMobile() {
        this.mobileOpen = true;
    },

    closeMobile() {
        this.mobileOpen = false;
    },

    init() {
        document.documentElement.classList.toggle('pb-sidebar-collapsed', this.collapsed);

        window.addEventListener('storage', (e) => {
            if (e.key === 'sidebarCollapsed') {
                this.collapsed = e.newValue === 'true';
                document.documentElement.classList.toggle('pb-sidebar-collapsed', this.collapsed);
            }
        });

        window.addEventListener('sidebarToggle', () => {
            this.collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            document.documentElement.classList.toggle('pb-sidebar-collapsed', this.collapsed);
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                this.mobileOpen = false;
            }
        });
    },
};

Alpine.store('sidebar', pbSidebarStore);
pbSidebarStore.init();

window.notificationDropdownComponent = function notificationDropdownComponent(el) {
    const getJson = (attr, fallback) => {
        try {
            const value = el.dataset[attr];
            return value ? JSON.parse(value) : fallback;
        } catch (error) {
            console.error(`Invalid JSON in ${attr}:`, error);
            return fallback;
        }
    };

    return {
        open: false,
        notifications: [],
        unreadCount: 0,
        loading: false,
        routes: {
            recent: el.dataset.recentUrl || '',
            markReadTemplate: el.dataset.markReadTemplate || '',
            markAllRead: el.dataset.markAllReadUrl || '',
        },
        ispId: Number(el.dataset.ispId || 0),
        refreshTimer: null,
        echoChannel: null,
        pollingEnabled: false,

        init() {
            const serverNotifications = getJson('notifications', []);
            this.notifications = serverNotifications.map((notification) => ({
                ...notification,
                time_ago: notification.time_ago || this.getTimeAgo(notification.created_at),
            }));
            this.unreadCount = Number(el.dataset.unreadCount || 0);

            this.startPolling();

            if (this.canUseRealtimePush()) {
                this.bindRealtimePush();
            }
        },

        canUseRealtimePush() {
            return Boolean(
                window.Echo
                && typeof window.Echo.private === 'function'
                && this.ispId > 0
            );
        },

        startPolling() {
            if (!this.routes.recent || this.ispId <= 0) {
                return;
            }

            this.pollingEnabled = true;
            this.refreshNotifications({ silent: true });

            this.refreshTimer = window.setInterval(() => {
                this.refreshNotifications({ silent: true });
            }, 30000);
        },

        bindRealtimePush() {
            if (!this.canUseRealtimePush()) {
                return;
            }

            try {
                this.echoChannel = window.Echo.private(`notifications.${this.ispId}`);
                this.echoChannel.listen('NewNotification', (event) => {
                    const payload = event?.notification;
                    if (!payload) {
                        return;
                    }

                    const incoming = {
                        ...payload,
                        time_ago: this.getTimeAgo(payload.created_at),
                    };
                    this.notifications.unshift(incoming);
                    this.unreadCount += 1;
                    this.showToast(incoming);
                });
            } catch (error) {
                if (typeof console !== 'undefined' && typeof console.debug === 'function') {
                    console.debug('Realtime notifications unavailable; using polling fallback.', error);
                }
            }
        },

        destroy() {
            if (this.refreshTimer) {
                window.clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }

            if (this.echoChannel && typeof this.echoChannel.stopListening === 'function') {
                this.echoChannel.stopListening('NewNotification');
                this.echoChannel = null;
            }
        },

        closeDropdown() {
            this.open = false;
        },

        getTimeAgo(createdAt) {
            if (!createdAt) return 'just now';
            const createdDate = new Date(createdAt);
            const now = new Date();
            const seconds = Math.floor((now - createdDate) / 1000);

            if (Number.isNaN(seconds) || seconds < 60) return 'just now';
            if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
            if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
            return `${Math.floor(seconds / 86400)} days ago`;
        },

        async refreshNotifications(options = {}) {
            const silent = Boolean(options.silent);

            if (!this.pollingEnabled || !this.routes.recent || this.ispId <= 0) {
                return;
            }

            if (this.loading) {
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(this.routes.recent, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                this.notifications = (data.notifications || []).map((notification) => ({
                    ...notification,
                    time_ago: notification.time_ago || this.getTimeAgo(notification.created_at),
                }));
                this.unreadCount = Number(data.unread_count || 0);
            } catch (error) {
                if (!silent && typeof console !== 'undefined' && typeof console.error === 'function') {
                    console.error('Failed to refresh notifications:', error);
                }
            } finally {
                this.loading = false;
            }
        },

        async markAsRead(notificationId) {
            const url = this.routes.markReadTemplate.replace(':id', notificationId);
            if (!url) return;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                });

                const data = await response.json();
                if (data.success) {
                    const target = this.notifications.find((notification) => notification.id === notificationId);
                    if (target && !target.is_read) {
                        target.is_read = true;
                        this.unreadCount = Math.max(0, this.unreadCount - 1);
                    }
                }
            } catch (error) {
                console.error('Failed to mark notification as read:', error);
            }
        },

        async markAllAsRead() {
            if (!this.routes.markAllRead) return;

            try {
                const response = await fetch(this.routes.markAllRead, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.notifications = this.notifications.map((notification) => ({
                        ...notification,
                        is_read: true,
                    }));
                    this.unreadCount = 0;
                }
            } catch (error) {
                console.error('Failed to mark all notifications as read:', error);
            }
        },

        getTypeMeta(type) {
            const normalized = String(type || 'info').toLowerCase();
            const map = {
                success: { color: '#10b981', label: 'Success' },
                warning: { color: '#f59e0b', label: 'Warning' },
                error: { color: '#ef4444', label: 'Error' },
                payment: { color: '#3b82f6', label: 'Payment' },
                subscription: { color: '#8b5cf6', label: 'Subscription' },
                hotspot: { color: '#6366f1', label: 'Hotspot' },
                info: { color: '#0ea5e9', label: 'Info' },
            };

            return map[normalized] || map.info;
        },

        showToast(notification) {
            const containerId = 'dashboard-toast-container';
            let container = document.getElementById(containerId);
            if (!container) {
                container = document.createElement('div');
                container.id = containerId;
                container.className = 'toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('article');
            toast.className = 'toast-notification';
            toast.setAttribute('role', 'status');

            const typeMeta = this.getTypeMeta(notification.type);
            const closeButton = document.createElement('button');
            closeButton.className = 'toast-close';
            closeButton.type = 'button';
            closeButton.setAttribute('aria-label', 'Close notification');
            closeButton.innerHTML = '&times;';

            const badge = document.createElement('span');
            badge.className = 'toast-badge';
            badge.style.backgroundColor = typeMeta.color;
            badge.textContent = typeMeta.label;

            const title = document.createElement('p');
            title.className = 'toast-title';
            title.textContent = notification.title || 'Notification';

            const message = document.createElement('p');
            message.className = 'toast-message';
            message.textContent = notification.message || '';

            const body = document.createElement('div');
            body.className = 'toast-body';
            body.append(badge, title, message);

            toast.append(body, closeButton);
            container.appendChild(toast);

            requestAnimationFrame(() => toast.classList.add('is-visible'));

            const removeToast = () => {
                toast.classList.remove('is-visible');
                setTimeout(() => {
                    toast.remove();
                    if (container && container.childElementCount === 0) {
                        container.remove();
                    }
                }, 300);
            };

            closeButton.addEventListener('click', removeToast);
            setTimeout(removeToast, 5000);
        },
    };
};

function parsePbQuickCreatePayload(meta) {
    if (! meta?.dataset?.quickCreate) {
        return [];
    }

    try {
        const parsed = JSON.parse(meta.dataset.quickCreate);

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function pbQuickCreateMarkup(items, createLabel = 'Create') {
    if (! Array.isArray(items) || items.length === 0) {
        return '';
    }

    const labelCreate = escapeHtml(createLabel);

    const links = items.map((item) => {
        const label = escapeHtml(item.label);
        const href = escapeHtml(item.href);
        const modalAttr = item.modal ? ' data-pb-modal-open' : ' data-turbo-frame="pb-main"';

        return `<a href="${href}"${modalAttr} class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">${label}</a>`;
    }).join('');

    return `
        <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                @click="open = !open"
                class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-br from-indigo-500 to-cyan-500 px-3 py-2 text-xs font-semibold text-white shadow-[0_8px_18px_-10px_rgba(99,102,241,0.6)] hover:from-indigo-600 hover:to-cyan-600"
                aria-haspopup="true"
                :aria-expanded="open"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14"/><path d="M5 12h14"/>
                </svg>
                <span class="hidden sm:inline">${labelCreate}</span>
            </button>
            <div
                x-show="open"
                x-cloak
                x-transition
                class="absolute right-0 z-50 mt-2 w-52 origin-top-right rounded-xl border border-slate-200 bg-white py-1 shadow-xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900"
            >
                ${links}
            </div>
        </div>
    `;
}

function syncPbQuickCreateFromFrame() {
    const meta = document.getElementById('pb-route-meta');
    const root = document.getElementById('pb-quick-create');

    if (! root) {
        return;
    }

    const items = parsePbQuickCreatePayload(meta);
    const markup = pbQuickCreateMarkup(items, meta?.dataset?.i18nCreate ?? 'Create');

    root.innerHTML = markup;

    const quickActions = document.querySelector('[data-pb-quick-actions]');
    if (quickActions) {
        quickActions.classList.toggle('hidden', items.length > 0);
    }

    if (markup && window.Alpine) {
        window.Alpine.initTree(root);
    }
}

function pbNavActiveExtraClasses(link) {
    return String(link?.dataset?.navActiveClass ?? '')
        .split(/\s+/)
        .filter(Boolean);
}

window.pbToggleGatewaySecret = function pbToggleGatewaySecret(inputId, trigger) {
    const input = document.getElementById(inputId);
    if (!input) {
        return;
    }

    const reveal = input.type === 'password';
    input.type = reveal ? 'text' : 'password';
    trigger?.setAttribute('aria-label', reveal ? 'Hide credential' : 'Show credential');
};

window.pbCopyFieldToClipboard = function pbCopyFieldToClipboard(inputId, trigger) {
    const input = document.getElementById(inputId);
    if (!input?.value) {
        return;
    }

    const value = input.value;
    const done = () => {
        if (!trigger) {
            return;
        }

        const original = trigger.dataset.copyLabel ?? trigger.textContent ?? 'Copy link';
        trigger.dataset.copyLabel = original;
        trigger.textContent = 'Copied';
        window.setTimeout(() => {
            trigger.textContent = original;
        }, 1800);
    };

    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(value).then(done).catch(() => {
            input.select();
            document.execCommand('copy');
            done();
        });

        return;
    }

    input.select();
    document.execCommand('copy');
    done();
};

function syncPbSidebarActiveState() {
    const meta = document.getElementById('pb-route-meta');
    const currentRoute = meta?.dataset?.route ?? '';
    const sidebarLinks = document.querySelectorAll('.pb-sidebar [data-nav-active-routes]');

    sidebarLinks.forEach((link) => {
        link.classList.remove('is-active');
        pbNavActiveExtraClasses(link).forEach((className) => link.classList.remove(className));
    });

    if (! currentRoute) {
        window.dispatchEvent(new CustomEvent('pb:sidebar-sync'));

        return;
    }

    sidebarLinks.forEach((link) => {
        const patterns = String(link.dataset.navActiveRoutes ?? '')
            .split(',')
            .map((part) => part.trim())
            .filter(Boolean);

        const active = patterns.some((pattern) => pbRouteMatchesPattern(currentRoute, pattern));

        if (active) {
            link.classList.add('is-active');
            pbNavActiveExtraClasses(link).forEach((className) => link.classList.add(className));
        }
    });

    window.dispatchEvent(new CustomEvent('pb:sidebar-sync'));
}

function pbRouteMatchesPattern(route, pattern) {
    if (! route || ! pattern) {
        return false;
    }

    if (pattern.endsWith('.*')) {
        const prefix = pattern.slice(0, -2);

        return route === prefix || route.startsWith(`${prefix}.`);
    }

    return route === pattern;
}

function syncPbShellFromFrame() {
    syncPbQuickCreateFromFrame();
    syncPbSidebarActiveState();
}

Alpine.start();
