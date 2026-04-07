<header 
    x-data="{ 
        mobileMenuOpen: false,
        toggleMobileMenu() {
            this.mobileMenuOpen = !this.mobileMenuOpen;
        }
    }"
    class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 fixed top-0 left-64 right-0 z-50 transition-all duration-300 ease-in-out">
    <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
        <!-- Logo / Title -->
        <div class="flex items-center">
            <!-- Sidebar Toggle Button -->
            <button @click="$store.sidebar.toggle()" class="hidden md:block p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 mr-4 focus:outline-none">
                <svg x-show="$store.sidebar.collapsed" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                </svg>
                <svg x-show="!$store.sidebar.collapsed" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                </svg>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center">
                <span class="text-xl font-bold text-gray-800 dark:text-gray-200">
                    ISP Billing Admin
                </span>
            </a>
        </div>

        <!-- Right Side -->
        <div class="flex items-center space-x-4">
            <!-- Mobile Menu Toggle -->
            <button 
                @click="toggleMobileMenu()"
                class="md:hidden p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <!-- Notifications (optional) -->
            <x-notification-dropdown :recentNotifications="$recentNotifications ?? null" :unreadCount="$unreadCount ?? 0" />

            <!-- User Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-3 text-sm focus:outline-none">
                    <div class="flex items-center space-x-2">
                        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="text-left hidden md:block">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Super Admin</div>
                        </div>
                    </div>
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50 border border-gray-200 dark:border-gray-700">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Profile Settings
                    </a>
                    <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                        User Dashboard
                    </a>
                    <hr class="my-1 border-gray-200 dark:border-gray-700">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.away="mobileMenuOpen = false"
         class="fixed inset-0 z-50 md:hidden">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="mobileMenuOpen = false"></div>
        
        <!-- Mobile Menu Panel -->
        <div class="fixed left-0 top-0 bottom-0 w-64 bg-white dark:bg-gray-800 shadow-xl overflow-y-auto">
            @include('layouts.admin-mobile-nav')
        </div>
    </div>
</header>

