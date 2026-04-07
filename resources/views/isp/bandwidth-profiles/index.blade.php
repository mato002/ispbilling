@extends('layouts.admin')

@section('title', 'Bandwidth Profiles')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Bandwidth Profiles') }}
        </h2>
        <a href="{{ route('isp.bandwidth-profiles.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Create Profile
        </a>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        <!-- Quick Start Guide -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-3">⚡ Bandwidth Profile Configuration</h3>
            <p class="text-blue-700 mb-4">The ISP admin defines speed limits for different service tiers:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg p-4 border border-blue-200">
                    <h4 class="font-semibold text-gray-800 mb-2">Example Profiles</h4>
                    <table class="text-sm w-full">
                        <tr class="border-b bg-gray-50">
                            <td class="py-2 font-medium">Basic</td>
                            <td class="py-2">5 Mbps</td>
                            <td class="py-2">2 Mbps</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 font-medium">Standard</td>
                            <td class="py-2">10 Mbps</td>
                            <td class="py-2">5 Mbps</td>
                        </tr>
                        <tr>
                            <td class="py-2 font-medium">Premium</td>
                            <td class="py-2">20 Mbps</td>
                            <td class="py-2">10 Mbps</td>
                        </tr>
                    </table>
                </div>
                
                <div class="bg-white rounded-lg p-4 border border-blue-200">
                    <h4 class="font-semibold text-gray-800 mb-2">Configuration Steps</h4>
                    <ol class="text-sm space-y-2 list-decimal list-inside">
                        <li>Click <strong>Create Profile</strong></li>
                        <li>Set download/upload speeds in Mbps</li>
                        <li>Configure burst limits (optional)</li>
                        <li>Set priority and queue type</li>
                        <li>Deploy to router for enforcement</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form method="GET" action="{{ route('isp.bandwidth-profiles.index') }}" class="flex gap-4">
                    <div>
                        <select name="is_active" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">All Status</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Filter
                    </button>
                </form>
            </div>
        </div>

        <!-- Bandwidth Profiles List -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Your Bandwidth Profiles</h3>
                
                @if($bandwidthProfiles->isEmpty())
                    <div class="text-center py-8">
                        <i class="fas fa-tachometer-alt text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400">No bandwidth profiles found.</p>
                        <p class="text-sm text-gray-400 mt-2">Create bandwidth profiles to define speed limits for your plans.</p>
                        <a href="{{ route('isp.bandwidth-profiles.create') }}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Create Profile
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Profile Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Download
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Upload
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Queue Type
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Priority
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($bandwidthProfiles as $profile)
                                    <tr class="{{ $profile->is_active ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-900/50' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fas fa-tachometer-alt text-blue-600 mr-2"></i>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $profile->name }}
                                                </div>
                                                @if($profile->parentProfile)
                                                    <div class="text-xs text-gray-500">Child of {{ $profile->parentProfile->name }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fas fa-download text-green-600 mr-1 text-xs"></i>
                                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $profile->formatted_download_speed }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <i class="fas fa-upload text-blue-600 mr-1 text-xs"></i>
                                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $profile->formatted_upload_speed }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $profile->queue_type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                {{ $profile->priority }}
                                                <div class="text-xs text-gray-500">{{ $profile->priority <= 3 ? 'High' : ($profile->priority <= 6 ? 'Medium' : 'Low') }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $profile->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                                {{ $profile->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="{{ route('isp.bandwidth-profiles.show', $profile) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('isp.bandwidth-profiles.edit', $profile) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($profile->is_active)
                                                    <form action="{{ route('isp.bandwidth-profiles.deactivate', $profile) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300" title="Deactivate">
                                                            <i class="fas fa-pause"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('isp.bandwidth-profiles.activate', $profile) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300" title="Activate">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $bandwidthProfiles->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
