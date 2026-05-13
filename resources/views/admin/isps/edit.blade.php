<x-admin-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit ISP') }}
            </h2>
            <a href="{{ route('admin.isps.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('admin.isps.update', $isp->id) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Basic Information</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ISP Name *</label>
                            <input type="text" name="name" value="{{ old('name', $isp->name) }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">URL slug (subdomain)</label>
                            <input type="text" name="slug" value="{{ old('slug', $isp->slug) }}"
                                   pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                   title="Lowercase letters, numbers, single hyphens"
                                   placeholder="e.g. techconnect"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <p class="mt-1 text-xs text-gray-500">Customer portal: <span class="font-mono">{{ parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https' }}://{slug}.{{ config('app.central_domain') }}</span></p>
                            @error('slug')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Company Name *</label>
                            <input type="text" name="company_name" value="{{ old('company_name', $isp->company_name) }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @error('company_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email *</label>
                            <input type="email" name="email" value="{{ old('email', $isp->email) }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone *</label>
                            <input type="text" name="phone" value="{{ old('phone', $isp->phone) }}" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @error('phone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Additional Information</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Address</label>
                            <textarea name="address" rows="3"
                                      class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('address', $isp->address) }}</textarea>
                            @error('address')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency *</label>
                            <select name="currency" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="KES" {{ old('currency', $isp->currency) == 'KES' ? 'selected' : '' }}>KES - Kenyan Shilling</option>
                                <option value="USD" {{ old('currency', $isp->currency) == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                <option value="TZS" {{ old('currency', $isp->currency) == 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                                <option value="UGX" {{ old('currency', $isp->currency) == 'UGX' ? 'selected' : '' }}>UGX - Ugandan Shilling</option>
                            </select>
                            @error('currency')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Timezone *</label>
                            <select name="timezone" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="Africa/Nairobi" {{ old('timezone', $isp->timezone) == 'Africa/Nairobi' ? 'selected' : '' }}>Africa/Nairobi</option>
                                <option value="Africa/Dar_es_Salaam" {{ old('timezone', $isp->timezone) == 'Africa/Dar_es_Salaam' ? 'selected' : '' }}>Africa/Dar es Salaam</option>
                                <option value="Africa/Kampala" {{ old('timezone', $isp->timezone) == 'Africa/Kampala' ? 'selected' : '' }}>Africa/Kampala</option>
                            </select>
                            @error('timezone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $isp->is_active) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</label>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4 space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Platform (SaaS) access</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">When locked, ISP admins and staff cannot open the ISP workspace until you clear this flag.</p>
                            <div class="flex items-center">
                                <input type="checkbox" name="platform_access_locked" value="1" {{ old('platform_access_locked', $isp->platform_access_locked) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Lock ISP dashboard (SaaS billing suspension)</label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lock message (shown to operators)</label>
                                <textarea name="platform_access_message" rows="2" placeholder="e.g. Please renew your PrimeBilling subscription."
                                          class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('platform_access_message', $isp->platform_access_message) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tier label</label>
                                <input type="text" name="saas_tier" value="{{ old('saas_tier', $isp->saas_tier) }}" placeholder="starter / pro / enterprise"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('admin.isps.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Update ISP
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>

