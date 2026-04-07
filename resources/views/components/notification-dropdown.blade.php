<div x-data="{ 
    open: false, 
    notifications: [],
    unreadCount: 0,
    loading: false,
    
    init() {
        // Initialize notifications from server data
        const serverData = @json($recentNotifications ?? []);
        this.notifications = serverData.map(notification => ({
            ...notification,
            time_ago: this.getTimeAgo(notification.created_at)
        }));
        this.unreadCount = {{ $unreadCount ?? 0 }};
        
        // Auto-refresh notifications every 30 seconds
        setInterval(() => {
            this.refreshNotifications();
        }, 30000);
        
        // Listen for real-time updates
        window.Echo?.private('notifications.' + {{ auth()->user()->isp_id }})
            .listen('NewNotification', (e) => {
                const newNotification = {
                    ...e.notification,
                    time_ago: this.getTimeAgo(e.notification.created_at)
                };
                this.notifications.unshift(newNotification);
                this.unreadCount++;
                this.showToast(newNotification);
            });
    },
    
    getTimeAgo(createdAt) {
        const date = new Date(createdAt);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
        return Math.floor(seconds / 86400) + ' days ago';
    },
    
    refreshNotifications() {
        this.loading = true;
        fetch('{{ route("isp.notifications.recent") }}')
            .then(response => response.json())
            .then(data => {
                this.notifications = data.notifications.map(notification => ({
                    ...notification,
                    time_ago: this.getTimeAgo(notification.created_at)
                }));
                this.unreadCount = data.unread_count;
            })
            .catch(error => console.error('Error refreshing notifications:', error))
            .finally(() => this.loading = false);
    },
    
    markAsRead(notificationId) {
        fetch(`{{ route("isp.notifications.mark-read", ":id") }}`.replace(':id', notificationId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.is_read = true;
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
            }
        });
    },
    
    markAllAsRead() {
        fetch('{{ route("isp.notifications.mark-all-read") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.notifications.forEach(n => n.is_read = true);
                this.unreadCount = 0;
            }
        });
    },
    
    showToast(notification) {
        // Create toast container if it doesn't exist
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 9999;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }
        
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.setAttribute('data-toast', 'true');
        toast.style.cssText = `
            background-color: ${this.getTypeColor(notification.type)};
            color: white;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            margin-bottom: 10px;
            max-width: 400px;
            pointer-events: auto;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            word-wrap: break-word;
        `;
        
        toast.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="flex-shrink: 0;">
                    <svg style="width: 24px; height: 24px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${notification.title}</div>
                    <div style="font-size: 14px; opacity: 0.9; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${notification.message}</div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; padding: 4px; flex-shrink: 0;">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        container.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                toast.remove();
                // Remove container if empty
                if (container.children.length === 0) {
                    container.remove();
                }
            }, 300);
        }, 5000);
    },
    
    getTypeColor(type) {
        const colors = {
            'success': '#10b981',
            'warning': '#f59e0b',
            'error': '#ef4444',
            'payment': '#3b82f6',
            'subscription': '#8b5cf6',
            'system': '#6b7280',
            'hotspot': '#6366f1',
            'info': '#3b82f6'
        };
        return colors[type] || colors.info;
    }
}">
    <div class="relative">
    <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        
        <!-- Unread Badge -->
        <span x-show="unreadCount > 0" 
              x-text="unreadCount > 99 ? '99+' : unreadCount"
              class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full min-w-[20px]">
        </span>
    </button>

    <!-- Dropdown -->
    <div x-show="open" 
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-md shadow-lg z-50 border border-gray-200 dark:border-gray-700">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Notifications</h3>
            <div class="flex items-center space-x-2">
                <button @click="refreshNotifications()" 
                        :disabled="loading"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 disabled:opacity-50">
                    <svg class="h-4 w-4" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
                <button x-show="unreadCount > 0" 
                        @click="markAllAsRead()"
                        class="text-xs text-blue-600 hover:text-blue-500 dark:text-blue-400">
                    Mark all read
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            <template x-for="notification in notifications" :key="notification.id">
                <div @click="!notification.is_read && markAsRead(notification.id)"
                     class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                     :class="{'bg-blue-50 dark:bg-blue-900/20': !notification.is_read}">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="h-8 w-8 rounded-full flex items-center justify-center"
                                 :style="'background-color: ' + getTypeColor(notification.type)">
                                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="notification.title"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="notification.message"></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="notification.time_ago"></p>
                        </div>
                        <div x-show="!notification.is_read" class="flex-shrink-0 mt-1">
                            <div class="h-2 w-2 bg-blue-600 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div x-show="notifications.length === 0" class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No notifications</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">You're all caught up!</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-3 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('isp.notifications.index') }}" 
               class="block text-center text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">
                View all notifications
            </a>
        </div>
    </div>
    </div>
</div>
