<aside 
    x-data="{ 
        dropdowns: {},
        toggleDropdown(name) {
            this.dropdowns[name] = !this.dropdowns[name];
        },
        isDropdownOpen(name) {
            return this.dropdowns[name] || false;
        }
    }"
    :class="$store.sidebar.collapsed ? 'w-16' : 'w-64'"
    class="bg-white dark:bg-gray-800 shadow-sm border-r border-gray-200 dark:border-gray-700 flex flex-col z-30 transition-all duration-300 ease-in-out hidden md:block">
    
    <!-- Sidebar Content -->
    <div class="flex-1 overflow-y-auto overflow-x-hidden py-2">
        <nav class="space-y-2" :class="$store.sidebar.collapsed ? 'px-2' : 'px-3'">
            @if(Auth::user()->isSuperAdmin())
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}" 
                   :class="$store.sidebar.collapsed ? 'px-2 py-3 justify-center' : 'px-3 py-2'">
                    <svg class="h-5 w-5" :class="$store.sidebar.collapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span x-show="!$store.sidebar.collapsed">Dashboard</span>
                </a>

                <!-- ISP Management -->
                <div x-show="!$store.sidebar.collapsed">
                    <button @click="toggleDropdown('isp')"
                           class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span>ISP Management</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isDropdownOpen('isp') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isDropdownOpen('isp')" class="mt-1 space-y-1">
                        <a href="{{ route('admin.isps.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            All ISPs
                        </a>
                        <a href="{{ route('admin.isps.create') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Create ISP
                        </a>
                    </div>
                </div>
                
                <!-- Collapsed ISP Management -->
                <a href="{{ route('admin.isps.index') }}" 
                   x-show="$store.sidebar.collapsed"
                   :title="'ISP Management'"
                   class="flex items-center text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.isps.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} px-2 py-3 justify-center">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </a>

                <!-- User Management -->
                <div x-show="!$store.sidebar.collapsed">
                    <button @click="toggleDropdown('users')"
                           class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span>User Management</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isDropdownOpen('users') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isDropdownOpen('users')" class="mt-1 space-y-1">
                        <a href="{{ route('admin.users.index', ['role' => 'super_admin']) }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Super Admins
                        </a>
                        <a href="{{ route('admin.users.index', ['role' => 'isp_admin']) }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            ISP Admins
                        </a>
                        <a href="{{ route('admin.users.index', ['role' => 'isp_staff']) }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            ISP Staff
                        </a>
                    </div>
                </div>
                
                <!-- Collapsed User Management -->
                <a href="{{ route('admin.users.index') }}" 
                   x-show="$store.sidebar.collapsed"
                   :title="'User Management'"
                   class="flex items-center text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} px-2 py-3 justify-center">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </a>

            @elseif(Auth::user()->isIspAdmin() || Auth::user()->isIspStaff())
                <!-- Dashboard -->
                <a href="{{ route('isp.dashboard') }}" 
                   class="flex items-center text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('isp.dashboard') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}" 
                   :class="$store.sidebar.collapsed ? 'px-2 py-3 justify-center' : 'px-3 py-2'">
                    <svg class="h-5 w-5" :class="$store.sidebar.collapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span x-show="!$store.sidebar.collapsed">Dashboard</span>
                </a>

                <!-- Customer Management -->
                <div x-show="!$store.sidebar.collapsed">
                    <button @click="toggleDropdown('customers')"
                           class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span>Customer Management</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isDropdownOpen('customers') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isDropdownOpen('customers')" class="mt-1 space-y-1">
                        <a href="{{ route('isp.customers.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            All Customers
                        </a>
                        <a href="{{ route('isp.subscriptions.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Subscriptions
                        </a>
                        <a href="{{ route('isp.user-connections.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            User Connections
                        </a>
                        <a href="{{ route('isp.active-users.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Active Users
                        </a>
                    </div>
                </div>
                
                <!-- Collapsed Customer Management -->
                <a href="{{ route('isp.customers.index') }}" 
                   x-show="$store.sidebar.collapsed"
                   :title="'Customer Management'"
                   class="flex items-center text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('isp.customers.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} px-2 py-3 justify-center">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </a>

                <!-- Network Management -->
                <div x-show="!$store.sidebar.collapsed">
                    <button @click="toggleDropdown('network')"
                           class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                            <span>Network Management</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isDropdownOpen('network') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isDropdownOpen('network')" class="mt-1 space-y-1">
                        <a href="{{ route('isp.routers.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Routers
                        </a>
                        <a href="{{ route('isp.hotspot-servers.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Hotspot Servers
                        </a>
                        <a href="{{ route('isp.ip-pools.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            IP Pools
                        </a>
                        <a href="{{ route('isp.bandwidth-profiles.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Bandwidth Profiles
                        </a>
                    </div>
                </div>
                
                <!-- Collapsed Network Management -->
                <a href="{{ route('isp.routers.index') }}" 
                   x-show="$store.sidebar.collapsed"
                   :title="'Network Management'"
                   class="flex items-center text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('isp.routers.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} px-2 py-3 justify-center">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </a>

                <!-- Services -->
                <div x-show="!$store.sidebar.collapsed">
                    <button @click="toggleDropdown('services')"
                           class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <span>Services</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isDropdownOpen('services') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isDropdownOpen('services')" class="mt-1 space-y-1">
                        <a href="{{ route('isp.plans.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Plans
                        </a>
                        <a href="{{ route('isp.vouchers.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Vouchers
                        </a>
                    </div>
                </div>
                
                <!-- Collapsed Services -->
                <a href="{{ route('isp.plans.index') }}" 
                   x-show="$store.sidebar.collapsed"
                   :title="'Services'"
                   class="flex items-center text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('isp.plans.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} px-2 py-3 justify-center">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </a>

                <!-- Financial -->
                <div x-show="!$store.sidebar.collapsed">
                    <button @click="toggleDropdown('financial')"
                           class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span>Financial</span>
                        </div>
                        <svg class="w-4 h-4 transition-transform" :class="isDropdownOpen('financial') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isDropdownOpen('financial')" class="mt-1 space-y-1">
                        <a href="{{ route('isp.invoices.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Invoices
                        </a>
                        <a href="{{ route('isp.payments.index') }}" 
                           class="block px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg ml-8">
                            Payments
                        </a>
                    </div>
                </div>
                
                <!-- Collapsed Financial -->
                <a href="{{ route('isp.invoices.index') }}" 
                   x-show="$store.sidebar.collapsed"
                   :title="'Financial'"
                   class="flex items-center text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('isp.invoices.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }} px-2 py-3 justify-center">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </a>

            @endif
        </nav>
    </div>

    <!-- Fixed Bottom Navigation -->
    <div class="border-t border-gray-200 dark:border-gray-700 p-3">
        <div class="space-y-2">
            <a href="{{ route('hotspot.packages') }}" 
               class="flex items-center text-sm font-medium rounded-lg transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700" 
               :class="$store.sidebar.collapsed ? 'px-2 py-3 justify-center' : 'px-3 py-2'">
                <svg class="h-5 w-5" :class="$store.sidebar.collapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span x-show="!$store.sidebar.collapsed">Hotspot Packages</span>
            </a>
            
            <a href="{{ route('welcome') }}" 
               class="flex items-center text-sm font-medium rounded-lg transition-colors text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700" 
               :class="$store.sidebar.collapsed ? 'px-2 py-3 justify-center' : 'px-3 py-2'">
                <svg class="h-5 w-5" :class="$store.sidebar.collapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
                <span x-show="!$store.sidebar.collapsed">Welcome Page</span>
            </a>
        </div>
    </div>
</aside>
