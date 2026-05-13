import './bootstrap';

import '@hotwired/turbo';
import Alpine from 'alpinejs';

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
    const main = newBody.querySelector('main');
    if (main) {
        window.Alpine.initTree(main);
    }
});

document.addEventListener('turbo:load', () => {
    syncCsrfFromMeta();
    processPbFlashPayload();
});

function processPbFlashPayload() {
    if (!window.Swal) {
        return;
    }
    const el = document.getElementById('pb-flash-payload');
    if (!el) {
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
            title: 'Error!',
            text: payload.error,
            confirmButtonColor: '#ef4444',
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
            title: 'Validation Error',
            text: payload.validation_errors.join('\n'),
            confirmButtonColor: '#ef4444',
        });
    }
}

window.Alpine = Alpine;

// Create a global store for sidebar state
Alpine.store('sidebar', {
    collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    mobileOpen: false,
    
    toggle() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('sidebarCollapsed', this.collapsed);
        window.dispatchEvent(new Event('sidebarToggle'));
    },

    openMobile() {
        this.mobileOpen = true;
    },

    closeMobile() {
        this.mobileOpen = false;
    },
    
    init() {
        // Listen for storage changes
        window.addEventListener('storage', (e) => {
            if (e.key === 'sidebarCollapsed') {
                this.collapsed = e.newValue === 'true';
            }
        });
        
        // Listen for custom toggle events
        window.addEventListener('sidebarToggle', () => {
            this.collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        });

        // Ensure mobile drawer closes on desktop resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                this.mobileOpen = false;
            }
        });
    }
});

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

        init() {
            const serverNotifications = getJson('notifications', []);
            this.notifications = serverNotifications.map((notification) => ({
                ...notification,
                time_ago: notification.time_ago || this.getTimeAgo(notification.created_at),
            }));
            this.unreadCount = Number(el.dataset.unreadCount || 0);

            this.refreshTimer = setInterval(() => this.refreshNotifications(), 30000);

            if (window.Echo && this.ispId > 0) {
                window.Echo.private(`notifications.${this.ispId}`)
                    .listen('NewNotification', (event) => {
                        const incoming = {
                            ...event.notification,
                            time_ago: this.getTimeAgo(event.notification.created_at),
                        };
                        this.notifications.unshift(incoming);
                        this.unreadCount += 1;
                        this.showToast(incoming);
                    });
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

        async refreshNotifications() {
            if (!this.routes.recent) return;

            this.loading = true;
            try {
                const response = await fetch(this.routes.recent, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                this.notifications = (data.notifications || []).map((notification) => ({
                    ...notification,
                    time_ago: notification.time_ago || this.getTimeAgo(notification.created_at),
                }));
                this.unreadCount = Number(data.unread_count || 0);
            } catch (error) {
                console.error('Failed to refresh notifications:', error);
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

Alpine.start();
