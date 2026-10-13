<aside class="w-64 bg-gray-800 dark:bg-gray-900 fixed left-0 top-0 bottom-0 z-40 h-screen">
    <div class="h-full px-3 py-4 overflow-y-auto overflow-x-hidden sidebar-scroll" style="scrollbar-width: thin; scrollbar-color: #4B5563 #1F2937;">
        <ul class="space-y-2 font-medium">
            @php
                $items = [
                    ['route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'gauge-high', 'label' => 'Dashboard'],
                    ['route' => 'isp.customers.index', 'active' => 'isp.customers.*', 'icon' => 'users', 'label' => 'Customers'],
                    ['route' => 'isp.plans.index', 'active' => 'isp.plans.*', 'icon' => 'list-alt', 'label' => 'Plans'],
                    ['route' => 'isp.routers.index', 'active' => 'isp.routers.*', 'icon' => 'house-signal', 'label' => 'Routers'],
                    ['route' => 'isp.subscriptions.index', 'active' => 'isp.subscriptions.*', 'icon' => 'circle-check', 'label' => 'Subscriptions'],
                    ['route' => 'isp.invoices.index', 'active' => 'isp.invoices.*', 'icon' => 'file-invoice', 'label' => 'Invoices'],
                    ['route' => 'isp.payments.index', 'active' => 'isp.payments.*', 'icon' => 'money-bill-wave', 'label' => 'Payments'],
                    ['route' => 'isp.reports.index', 'active' => 'isp.reports.*', 'icon' => 'chart-column', 'label' => 'Reports'],
                    ['route' => 'isp.notifications.index', 'active' => 'isp.notifications.*', 'icon' => 'bell', 'label' => 'Notifications'],
                    ['route' => 'isp.settings.index', 'active' => 'isp.settings.*', 'icon' => 'gear', 'label' => 'Settings'],
                ];
            @endphp
            @foreach ($items as $item)
                <li>
                    <a href="{{ route($item['route']) }}" class="flex items-center gap-3 p-2 text-gray-300 rounded-lg hover:bg-gray-700 {{ request()->routeIs($item['active']) ? 'bg-gray-700' : '' }}">
                        <x-font-awesome-icon :name="$item['icon']" class="h-5 w-5 text-gray-400 transition duration-75" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
