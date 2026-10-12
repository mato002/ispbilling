<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('System Settings') }}
        </h2>
    </x-slot>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Application Settings -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Application Settings</h3>
                    
                    <form method="POST" action="{{ route('admin.settings.update') }}">
                        @csrf
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Application Name</label>
                                <input type="text" name="app_name" value="{{ $settings['app_name'] }}" 
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Application URL</label>
                                <input type="url" name="app_url" value="{{ $settings['app_url'] }}" 
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Timezone</label>
                                <select name="timezone" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="Africa/Nairobi" {{ $settings['timezone'] == 'Africa/Nairobi' ? 'selected' : '' }}>Africa/Nairobi</option>
                                    <option value="UTC" {{ $settings['timezone'] == 'UTC' ? 'selected' : '' }}>UTC</option>
                                    <option value="America/New_York" {{ $settings['timezone'] == 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                                    <option value="Europe/London" {{ $settings['timezone'] == 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Locale</label>
                                <select name="locale" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="en" {{ $settings['locale'] == 'en' ? 'selected' : '' }}>English</option>
                                    <option value="sw" {{ $settings['locale'] == 'sw' ? 'selected' : '' }}>Swahili</option>
                                </select>
                            </div>

                            <div class="pt-4">
                                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    Save Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Information -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">System Information</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Environment</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                <span class="px-2 py-1 rounded {{ $settings['app_env'] == 'production' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ strtoupper($settings['app_env']) }}
                                </span>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Debug Mode</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $settings['app_debug'] ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Laravel Version</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ app()->version() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">PHP Version</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ PHP_VERSION }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Form controls</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Manage field visibility, requirements, and defaults for platform admin forms.
                    </p>
                    <a href="{{ route('admin.settings.forms.index') }}" data-turbo-frame="pb-main" data-turbo-action="advance" class="inline-flex px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">
                        Open forms control center
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
                    
                    <div class="space-y-3">
                        <form method="POST" action="{{ route('admin.settings.clear-cache') }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 text-sm">
                                Clear Cache
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.settings.optimize') }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 text-sm">
                                Optimize Application
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Maintenance Mode -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Maintenance</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Put the application in maintenance mode for updates.
                    </p>
                    <div class="space-y-2">
                        <form method="POST" action="{{ route('admin.settings.maintenance') }}">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 {{ $maintenanceEnabled ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-500 hover:bg-red-600' }} text-white rounded-md text-sm">
                                {{ $maintenanceEnabled ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode' }}
                            </button>
                        </form>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Current status: {{ $maintenanceEnabled ? 'Enabled' : 'Disabled' }}</p>
                    </div>
                </div>
            </div>

            <!-- Backup -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Backup</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Create a backup of your database.
                    </p>
                    <form method="POST" action="{{ route('admin.settings.backup') }}">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm">Download Database Backup</button>
                    </form>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Downloads a JSON export of all database tables.</p>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

