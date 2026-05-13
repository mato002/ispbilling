<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('User Details') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.users.edit', $user->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Edit
                </a>
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $user->email }}</p>
                        </div>
                        @if($user->is_active)
                            <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Active
                            </span>
                        @else
                            <span class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                Inactive
                            </span>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->phone ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</dt>
                            <dd class="mt-1">
                                @switch($user->role)
                                    @case('super_admin')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100">
                                            Super Admin
                                        </span>
                                        @break
                                    @case('isp_admin')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                            ISP Admin
                                        </span>
                                        @break
                                    @case('isp_staff')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            Staff
                                        </span>
                                        @break
                                @endswitch
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ISP</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $user->isp ? $user->isp->name : 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Verified</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if($user->email_verified_at)
                                    <span class="text-green-600">Yes ({{ $user->email_verified_at->format('M d, Y') }})</span>
                                @else
                                    <span class="text-red-600">No</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->updated_at->format('M d, Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Additional Information -->
            @if($user->isp)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">ISP Information</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ISP Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->isp->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Company</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->isp->company_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ISP Email</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->isp->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ISP Phone</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->isp->phone }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Edit User
                        </a>
                        @if($user->is_active)
                            <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="inactive">
                                <button type="submit" class="w-full px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400">
                                    Deactivate
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="w-full px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 dark:bg-green-900/20 dark:text-green-400">
                                    Activate
                                </button>
                            </form>
                        @endif
                        @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="confirmFormSubmit(event, {title: 'Delete User', text: 'Are you sure you want to delete {{ $user->name }}? This action cannot be undone.', icon: 'warning', confirmButtonText: 'Yes, delete', confirmButtonColor: '#ef4444'}); return false;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 dark:bg-red-900/20 dark:text-red-400">
                                    Delete User
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Permissions Summary -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Role Permissions</h3>
                    <div class="space-y-2 text-sm">
                        @switch($user->role)
                            @case('super_admin')
                                <div class="text-gray-700 dark:text-gray-300">
                                    <p class="font-medium">Super Admin has full access to:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>All ISP management</li>
                                        <li>User management</li>
                                        <li>System settings</li>
                                        <li>Reports and analytics</li>
                                        <li>Customer data across all ISPs</li>
                                    </ul>
                                </div>
                                @break
                            @case('isp_admin')
                                <div class="text-gray-700 dark:text-gray-300">
                                    <p class="font-medium">ISP Admin has access to:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>Own ISP management</li>
                                        <li>Customer management</li>
                                        <li>Plan and router management</li>
                                        <li>Billing and payments</li>
                                        <li>Reports for their ISP</li>
                                    </ul>
                                </div>
                                @break
                            @case('isp_staff')
                                <div class="text-gray-700 dark:text-gray-300">
                                    <p class="font-medium">Staff has access to:</p>
                                    <ul class="list-disc list-inside mt-2 space-y-1">
                                        <li>Customer management (limited)</li>
                                        <li>View reports</li>
                                        <li>Process payments</li>
                                        <li>Manage tickets</li>
                                    </ul>
                                </div>
                                @break
                        @endswitch
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
