@switch($key)
    @case('dashboard')
        <x-font-awesome-icon name="grid-horizontal" class="pb-nav-icon" />
        @break
    @case('operations')
        <x-font-awesome-icon name="building-user" class="pb-nav-icon" />
        @break
    @case('network')
        <x-font-awesome-icon name="desktop" class="pb-nav-icon" />
        @break
    @case('support')
        <x-font-awesome-icon name="comments" class="pb-nav-icon" />
        @break
    @case('analytics')
        <x-font-awesome-icon name="chart-line" class="pb-nav-icon" />
        @break
    @case('settings')
        <x-font-awesome-icon name="gear" class="pb-nav-icon" />
        @break
    @case('platform')
        <x-font-awesome-icon name="building" class="pb-nav-icon" />
        @break
    @case('field')
        <x-font-awesome-icon name="cube" class="pb-nav-icon" />
        @break
    @default
        <x-font-awesome-icon name="circle" class="pb-nav-icon" />
@endswitch
