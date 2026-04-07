<aside class="w-64 bg-gray-800 dark:bg-gray-900 fixed left-0 top-0 bottom-0 z-40 h-screen">
    <div class="h-full px-3 py-4 overflow-y-auto overflow-x-hidden sidebar-scroll" style="scrollbar-width: thin; scrollbar-color: #4B5563 #1F2937;">
        <ul class="space-y-2 font-medium">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('dashboard') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="ms-3">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('isp.customers.index') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('isp.customers.*') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="ms-3">Customers</span>
                </a>
            </li>
            <li>
                <a href="{{ route('isp.plans.index') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('isp.plans.*') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="ms-3">Plans</span>
                </a>
            </li>
            <li>
                <a href="{{ route('isp.routers.index') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('isp.routers.*') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                    </svg>
                    <span class="ms-3">Routers</span>
                </a>
            </li>
            <li>
                <a href="{{ route('isp.subscriptions.index') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('isp.subscriptions.*') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="ms-3">Subscriptions</span>
                </a>
            </li>
            <li>
                <a href="{{ route('isp.invoices.index') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('isp.invoices.*') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="ms-3">Invoices</span>
                </a>
            </li>
            <li>
                <a href="{{ route('isp.payments.index') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('isp.payments.*') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="ms-3">Payments</span>
                </a>
            </li>
            <li>
                <a href="{{ route('isp.reports.index') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('isp.reports.*') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="ms-3">Reports</span>
                </a>
            </li>
            <li>
                <a href="{{ route('isp.notifications.index') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('isp.notifications.*') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="ms-3">Notifications</span>
                </a>
            </li>
            <li>
                <a href="{{ route('isp.settings.index') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs('isp.settings.*') ? 'bg-gray-700' : '' }}">
                    <svg class="w-5 h-5 text-gray-400 transition duration-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="ms-3">Settings</span>
                </a>
            </li>
        </ul>
    </div>
</aside>

