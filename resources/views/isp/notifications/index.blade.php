@extends('layouts.admin')

@section('title', 'Notifications')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Notifications') }}
        </h2>
        @if($unreadCount > 0)
            <form method="POST" action="{{ route('isp.notifications.mark-all-read') }}" class="inline" id="markAllReadForm">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Mark All as Read
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('isp.notifications.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All ({{ $totalCount }})</option>
                        <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Unread ({{ $unreadCount }})</option>
                        <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                    <select name="type" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">All Types</option>
                        <option value="payment" {{ request('type') == 'payment' ? 'selected' : '' }}>Payment</option>
                        <option value="subscription" {{ request('type') == 'subscription' ? 'selected' : '' }}>Subscription</option>
                        <option value="system" {{ request('type') == 'system' ? 'selected' : '' }}>System</option>
                        <option value="hotspot" {{ request('type') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                        <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Success</option>
                        <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="error" {{ request('type') == 'error' ? 'selected' : '' }}>Error</option>
                        <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                </div>

                <div class="md:col-span-4 flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Filter
                    </button>
                    @if(request()->hasAny(['status', 'type', 'date_from', 'date_to']))
                        <a href="{{ route('isp.notifications.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            @forelse($notifications as $notification)
                @php
                    $borderColor = match($notification->type) {
                        'success' => 'border-green-500',
                        'warning' => 'border-yellow-500',
                        'error' => 'border-red-500',
                        'payment' => 'border-blue-500',
                        'subscription' => 'border-purple-500',
                        'system' => 'border-gray-500',
                        'hotspot' => 'border-indigo-500',
                        default => 'border-blue-500',
                    };
                    $bgColor = match($notification->type) {
                        'success' => 'bg-green-50 dark:bg-green-900/20',
                        'warning' => 'bg-yellow-50 dark:bg-yellow-900/20',
                        'error' => 'bg-red-50 dark:bg-red-900/20',
                        'payment' => 'bg-blue-50 dark:bg-blue-900/20',
                        'subscription' => 'bg-purple-50 dark:bg-purple-900/20',
                        'system' => 'bg-gray-50 dark:bg-gray-900/20',
                        'hotspot' => 'bg-indigo-50 dark:bg-indigo-900/20',
                        default => 'bg-blue-50 dark:bg-blue-900/20',
                    };
                    $badgeColor = match($notification->type) {
                        'success' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
                        'error' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                        'payment' => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
                        'subscription' => 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100',
                        'system' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
                        'hotspot' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100',
                        default => 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
                    };
                @endphp
                <div class="border-l-4 {{ $notification->is_read ? 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50' : $borderColor . ' ' . $bgColor }} p-4 rounded mb-4 {{ !$notification->is_read ? 'font-semibold' : '' }}">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <h4 class="text-sm {{ !$notification->is_read ? 'font-bold' : 'font-medium' }} text-gray-900 dark:text-gray-100">
                                    {{ $notification->title }}
                                </h4>
                                @if(!$notification->is_read)
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                        New
                                    </span>
                                @endif
                                <span class="px-2 py-1 text-xs rounded-full {{ $badgeColor }}">
                                    {{ ucfirst($notification->type) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                {{ $notification->message }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $notification->created_at->diffForHumans() }} ({{ $notification->created_at->format('M d, Y H:i') }})
                            </p>
                        </div>
                        @if(!$notification->is_read)
                            <form method="POST" action="{{ route('isp.notifications.mark-read', $notification) }}" class="ml-4">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Mark as Read
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No notifications</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if(request()->hasAny(['status', 'type', 'date_from', 'date_to']))
                            No notifications match your filters.
                        @else
                            You're all caught up! No new notifications.
                        @endif
                    </p>
                </div>
            @endforelse

            <!-- Pagination -->
            @if($notifications->hasPages())
                <div class="mt-6">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        // Handle mark all as read
        document.getElementById('markAllReadForm')?.addEventListener('submit', function(e) {
            // Allow normal form submission for now, or use AJAX if preferred
            // The form will submit normally and reload the page
        });

        // Handle individual mark as read with AJAX (optional enhancement)
        document.querySelectorAll('form[action*="mark-read"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                // Allow normal form submission - page will reload
                // For AJAX, uncomment below:
                /*
                e.preventDefault();
                
                const notificationCard = this.closest('.border-l-4');
                const button = this.querySelector('button');
                
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI without reload
                        notificationCard.classList.remove('border-green-500', 'border-yellow-500', 'border-red-500', 'border-blue-500', 'border-purple-500', 'border-indigo-500', 'bg-green-50', 'bg-yellow-50', 'bg-red-50', 'bg-blue-50', 'bg-purple-50', 'bg-indigo-50', 'dark:bg-green-900/20', 'dark:bg-yellow-900/20', 'dark:bg-red-900/20', 'dark:bg-blue-900/20', 'dark:bg-purple-900/20', 'dark:bg-indigo-900/20', 'font-semibold');
                        notificationCard.classList.add('border-gray-300', 'dark:border-gray-600', 'bg-gray-50', 'dark:bg-gray-900/50');
                        const title = notificationCard.querySelector('h4');
                        if (title) {
                            title.classList.remove('font-bold');
                            title.classList.add('font-medium');
                        }
                        button.remove();
                    }
                });
                */
            });
        });
    </script>
@endsection
