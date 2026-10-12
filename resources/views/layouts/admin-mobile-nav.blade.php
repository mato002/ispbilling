<!-- Mobile Navigation Menu -->
<div 
    x-data="{
        dropdowns: {},
        toggleDropdown(name) {
            this.dropdowns[name] = !this.dropdowns[name];
        },
        isDropdownOpen(name) {
            return this.dropdowns[name] || false;
        },
        closeMenu() {
            if (this.$store?.sidebar) {
                this.$store.sidebar.closeMobile();
            }
        }
    }">
    
    <!-- Mobile Header -->
    <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700">
        <span class="text-lg font-semibold text-gray-800 dark:text-gray-200">Menu</span>
        <button @click="closeMenu()" class="p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
        
        <!-- Mobile Navigation -->
        <nav class="p-4 space-y-2">
            @if(Auth::user()->isSuperAdmin() && ! \\App\\Support\\SuperAdminWorkspaceContext::active())
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" 
                   @click="closeMenu()"
                   class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('admin.integrations.index') }}"
                   @click="closeMenu()"
                   class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Integrations
                </a>

                <!-- ISP Management -->
                <div x-data="{ isOpen: false }">
                    <button @click="isOpen = !isOpen" 
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span>ISP Management</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isOpen" class="mt-1 space-y-1">
                        <a href="{{ route('admin.isps.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            All ISPs
                        </a>
                        <a href="{{ route('admin.isps.create') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Create ISP
                        </a>
                    </div>
                </div>

                <!-- User Management -->
                <div x-data="{ isOpen: false }">
                    <button @click="isOpen = !isOpen" 
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span>User Management</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isOpen" class="mt-1 space-y-1">
                        <a href="{{ route('admin.users.index', ['role' => 'super_admin']) }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Super Admins
                        </a>
                        <a href="{{ route('admin.users.index', ['role' => 'isp_admin']) }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            ISP Admins
                        </a>
                        <a href="{{ route('admin.users.index', ['role' => 'isp_staff']) }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            ISP Staff
                        </a>
                    </div>
                </div>

            @elseif(Auth::user()->isIspAdmin() || Auth::user()->isIspStaff())
                <!-- Dashboard -->
                <a href="{{ route('isp.dashboard') }}" 
                   @click="closeMenu()"
                   class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>

                <!-- Customer Management -->
                <div x-data="{ isOpen: false }">
                    <button @click="isOpen = !isOpen" 
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span>Customer Management</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isOpen" class="mt-1 space-y-1">
                        <a href="{{ route('isp.customers.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            All Customers
                        </a>
                        <a href="{{ route('isp.subscriptions.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Subscriptions
                        </a>
                        <a href="{{ route('isp.user-connections.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            User Connections
                        </a>
                        <a href="{{ route('isp.active-users.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Active Users
                        </a>
                    </div>
                </div>

                <!-- Network Management -->
                <div x-data="{ isOpen: false }">
                    <button @click="isOpen = !isOpen" 
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                            <span>Network Management</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isOpen" class="mt-1 space-y-1">
                        <a href="{{ route('isp.routers.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Routers
                        </a>
                        <a href="{{ route('isp.hotspot-servers.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Hotspot Servers
                        </a>
                        <a href="{{ route('isp.ip-pools.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            IP Pools
                        </a>
                        <a href="{{ route('isp.bandwidth-profiles.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Bandwidth Profiles
                        </a>
                    </div>
                </div>

                <!-- Services -->
                <div x-data="{ isOpen: false }">
                    <button @click="isOpen = !isOpen" 
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <span>Services</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isOpen" class="mt-1 space-y-1">
                        <a href="{{ route('isp.plans.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Plans
                        </a>
                        <a href="{{ route('isp.vouchers.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Vouchers
                        </a>
                    </div>
                </div>

                <!-- Financial -->
                <div x-data="{ isOpen: false }">
                    <button @click="isOpen = !isOpen" 
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>Financial</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isOpen" class="mt-1 space-y-1">
                        <a href="{{ route('isp.invoices.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Invoices
                        </a>
                        <a href="{{ route('isp.payments.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Payments
                        </a>
                        <a href="{{ route('isp.reports.index') }}" 
                           @click="closeMenu()"
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Reports
                        </a>
                    </div>
                </div>
            @endif

            <!-- Quick Links -->
            <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                <p class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Quick Links</p>
                <a href="{{ route('hotspot.packages') }}" 
                   @click="closeMenu()"
                   class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    Hotspot Packages
                </a>
                <a href="{{ route('welcome') }}" 
                   @click="closeMenu()"
                   class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    Welcome Page
                </a>
            </div>
        </nav>
    </div>
</div>
