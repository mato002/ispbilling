<nav class="mb-6 flex flex-wrap gap-2 border-b border-[var(--pb-border)] pb-3">
    <a href="{{ route('isp.inventory.index') }}" class="pb-btn pb-btn-sm {{ request()->routeIs('isp.inventory.index', 'isp.inventory.show', 'isp.inventory.create', 'isp.inventory.edit') ? 'pb-btn-primary' : '' }}">All items</a>
    <a href="{{ route('isp.inventory.scan') }}" class="pb-btn pb-btn-sm {{ request()->routeIs('isp.inventory.scan*') ? 'pb-btn-primary' : '' }}">Scan device</a>
    <a href="{{ route('isp.inventory.assignments.index') }}" class="pb-btn pb-btn-sm {{ request()->routeIs('isp.inventory.assignments.*') ? 'pb-btn-primary' : '' }}">Assignments</a>
    <a href="{{ route('isp.inventory.technician-assignments.index') }}" class="pb-btn pb-btn-sm {{ request()->routeIs('isp.inventory.technician-assignments.*') ? 'pb-btn-primary' : '' }}">Technician issues</a>
    <a href="{{ route('isp.inventory.faulty') }}" class="pb-btn pb-btn-sm {{ request()->routeIs('isp.inventory.faulty') ? 'pb-btn-primary' : '' }}">Faulty</a>
    <a href="{{ route('isp.inventory.transactions') }}" class="pb-btn pb-btn-sm {{ request()->routeIs('isp.inventory.transactions') ? 'pb-btn-primary' : '' }}">History</a>
    <a href="{{ route('isp.inventory.purchases') }}" class="pb-btn pb-btn-sm {{ request()->routeIs('isp.inventory.purchases*') ? 'pb-btn-primary' : '' }}">Purchases</a>
    <a href="{{ route('isp.inventory.thresholds') }}" class="pb-btn pb-btn-sm {{ request()->routeIs('isp.inventory.thresholds*') ? 'pb-btn-primary' : '' }}">Low stock</a>
</nav>
