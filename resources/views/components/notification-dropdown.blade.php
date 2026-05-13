@php
    $serializedRecentNotifications = ($recentNotifications ?? collect())
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'message' => $item->message,
                'type' => $item->type,
                'is_read' => (bool) $item->is_read,
                'time_ago' => $item->created_at?->diffForHumans(),
                'created_at' => $item->created_at?->toISOString(),
            ];
        })
        ->values();
@endphp

<div
    x-data="notificationDropdownComponent($el)"
    data-notifications='@json($serializedRecentNotifications)'
    data-unread-count="{{ (int) ($unreadCount ?? 0) }}"
    data-recent-url="{{ route('isp.notifications.recent') }}"
    data-mark-read-template="{{ route('isp.notifications.mark-read', ':id') }}"
    data-mark-all-read-url="{{ route('isp.notifications.mark-all-read') }}"
    data-isp-id="{{ (int) (auth()->user()->isp_id ?? 0) }}"
>
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
         class="absolute right-0 mt-2 w-[22rem] max-w-[calc(100vw-1.5rem)] bg-white dark:bg-gray-800 rounded-xl shadow-lg z-50 border border-gray-200 dark:border-gray-700">
        
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
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No new notifications</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">You are all caught up.</p>
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
