@extends('layouts.admin')

@section('title', 'Add Router')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Router') }}
        </h2>
        <a href="{{ route('isp.routers.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
            Back to List
        </a>
    </div>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <form method="POST" action="{{ route('isp.routers.store') }}" id="routerForm" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Basic Information</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Router Name *</label>
                            <input type="text" name="name" id="name" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Main Router">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Router IP *</label>
                            <input type="text" name="ip_address" id="ip_address" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="192.168.88.1">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Router Location</label>
                            <input type="text" name="location"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="Main Building">
                        </div>
                    </div>

                    <!-- Connection Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Connection Settings</h3>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username *</label>
                            <input type="text" name="username" id="username" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="admin">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password *</label>
                            <input type="password" name="password" id="password" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   placeholder="*****">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Port *</label>
                            <input type="number" name="port" id="port" value="8728" required
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Default MikroTik API port is 8728</p>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="use_ssl" id="use_ssl" value="1"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Use SSL</label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" checked
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</label>
                        </div>
                    </div>
                </div>

                <!-- Test Connection Section -->
                <div class="border-t pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Test Connection</h3>
                        <button type="button" id="testConnectionBtn" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            Test Connection
                        </button>
                    </div>
                    
                    <!-- Connection Status -->
                    <div id="connectionStatus" class="hidden">
                        <!-- Results will be displayed here -->
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4 border-t pt-6">
                    <a href="{{ route('isp.routers.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Add Router
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Test Connection -->
    <script>
        document.getElementById('testConnectionBtn').addEventListener('click', function() {
            const btn = this;
            const statusDiv = document.getElementById('connectionStatus');
            
            // Get form values
            const formData = {
                ip_address: document.getElementById('ip_address').value,
                port: document.getElementById('port').value,
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
                use_ssl: document.getElementById('use_ssl').checked
            };

            // Validate required fields
            if (!formData.ip_address || !formData.port || !formData.username || !formData.password) {
                statusDiv.innerHTML = `
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="text-yellow-800 font-medium">Missing Required Fields</h4>
                                <p class="text-yellow-700 text-sm mt-1">Please fill in all connection settings before testing.</p>
                            </div>
                        </div>
                    </div>
                `;
                statusDiv.classList.remove('hidden');
                return;
            }

            // Show loading state
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Testing...
            `;

            statusDiv.innerHTML = `
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex">
                        <svg class="animate-spin h-5 w-5 text-blue-400 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <div>
                            <h4 class="text-blue-800 font-medium">Testing Connection</h4>
                            <p class="text-blue-700 text-sm mt-1">Attempting to connect to the router...</p>
                        </div>
                    </div>
                </div>
            `;
            statusDiv.classList.remove('hidden');

            // Make AJAX request
            fetch('{{ route("isp.routers.test-connection") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                btn.disabled = false;
                btn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    Test Connection
                `;

                if (data.success) {
                    // Success response
                    statusDiv.innerHTML = `
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex">
                                <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-green-800 font-medium">Connection Successful!</h4>
                                    <p class="text-green-700 text-sm mt-1">${data.message}</p>
                                    
                                    ${data.router_info ? `
                                        <div class="mt-3 p-3 bg-green-100 rounded">
                                            <h5 class="font-medium text-green-800 mb-2">Router Information:</h5>
                                            <div class="grid grid-cols-2 gap-2 text-sm">
                                                <div><strong>Identity:</strong> ${data.router_info.identity}</div>
                                                <div><strong>Version:</strong> ${data.router_info.version}</div>
                                                <div><strong>Uptime:</strong> ${data.router_info.uptime}</div>
                                                <div><strong>CPU Load:</strong> ${data.router_info.cpu_load}%</div>
                                                <div><strong>Memory:</strong> ${data.router_info.memory_free} / ${data.router_info.memory_total}</div>
                                            </div>
                                        </div>
                                    ` : ''}
                                    
                                    ${data.hotspot_servers_count > 0 ? `
                                        <div class="mt-3 p-3 bg-blue-100 rounded">
                                            <h5 class="font-medium text-blue-800 mb-2">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                                </svg>
                                                Found ${data.hotspot_servers_count} Hotspot Server(s)
                                            </h5>
                                            <p class="text-blue-700 text-sm">Hotspot servers will be automatically imported when you save this router.</p>
                                            ${data.hotspot_servers.length > 0 ? `
                                                <div class="mt-2 space-y-1">
                                                    ${data.hotspot_servers.map(server => `
                                                        <div class="text-xs text-blue-600">• ${server.name} (${server.interface})</div>
                                                    `).join('')}
                                                </div>
                                            ` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // Error response
                    statusDiv.innerHTML = `
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex">
                                <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h4 class="text-red-800 font-medium">Connection Failed</h4>
                                    <p class="text-red-700 text-sm mt-1">${data.error}</p>
                                    <div class="mt-2 text-xs text-red-600">
                                        Please check:<br>
                                        • Router IP address and port<br>
                                        • Username and password<br>
                                        • Network connectivity<br>
                                        • API service is enabled on router
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                // Reset button
                btn.disabled = false;
                btn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    Test Connection
                `;

                statusDiv.innerHTML = `
                    <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="text-red-800 font-medium">Connection Test Error</h4>
                                <p class="text-red-700 text-sm mt-1">An error occurred while testing the connection. Please try again.</p>
                            </div>
                        </div>
                    </div>
                `;
            });
        });
    </script>
@endsection

