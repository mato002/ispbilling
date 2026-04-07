@extends('layouts.admin')

@section('title', 'Voucher Details')

@section('header')
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Voucher Details') }}
        </h2>
        <a href="{{ route('isp.vouchers.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            Back to Vouchers
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-4xl mx-auto">
        <!-- Voucher Information -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Voucher Information</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Created {{ $voucher->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 text-sm font-medium rounded-full 
                            {{ $voucher->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $voucher->status === 'used' ? 'bg-gray-100 text-gray-800' : '' }}
                            {{ $voucher->status === 'expired' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($voucher->status) }}
                        </span>
                        <span class="px-3 py-1 text-sm font-medium rounded-full 
                            {{ $voucher->voucher_type === 'time' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $voucher->voucher_type === 'data' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $voucher->voucher_type === 'unlimited' ? 'bg-purple-100 text-purple-800' : '' }}">
                            {{ ucfirst($voucher->voucher_type) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Voucher Code</label>
                            <div class="mt-1 text-lg font-mono font-semibold text-gray-900 dark:text-gray-100">
                                {{ $voucher->voucher_code }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Router</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if($voucher->router)
                                    {{ $voucher->router->name }}
                                    <span class="text-gray-500">({{ $voucher->router->ip_address }})</span>
                                @else
                                    <span class="text-gray-500">No router assigned</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Plan</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if($voucher->plan)
                                    {{ $voucher->plan->name }}
                                    @if($voucher->plan->price)
                                        <span class="text-gray-500">- KES {{ number_format($voucher->plan->price, 0) }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-500">No plan assigned</span>
                                @endif
                            </div>
                        </div>

                        @if($voucher->speed_profile)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Speed Profile</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $voucher->speed_profile }}
                            </div>
                        </div>
                        @endif

                        @if($voucher->price)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Price</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                KES {{ number_format($voucher->price, 2) }}
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-4">
                        @if($voucher->voucher_type === 'time' && $voucher->time_limit_hours)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Time Limit</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $voucher->time_limit_hours }} hour{{ $voucher->time_limit_hours > 1 ? 's' : '' }}
                            </div>
                        </div>
                        @endif

                        @if($voucher->voucher_type === 'data' && $voucher->data_limit_mb)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Data Limit</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ number_format($voucher->data_limit_mb / 1024, 2) }} GB
                            </div>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Expires At</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if($voucher->expires_at)
                                    {{ $voucher->expires_at->format('M d, Y H:i') }}
                                    @if($voucher->expires_at->isPast())
                                        <span class="text-red-500">(Expired)</span>
                                    @endif
                                @else
                                    <span class="text-gray-500">No expiration</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Created By</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if($voucher->creator)
                                    {{ $voucher->creator->name }}
                                @else
                                    <span class="text-gray-500">System</span>
                                @endif
                            </div>
                        </div>

                        @if($voucher->notes)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Notes</label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $voucher->notes }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Information -->
        @if($voucher->status === 'used' && $voucher->usedByCustomer)
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Usage Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Used By</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $voucher->usedByCustomer->full_name }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $voucher->usedByCustomer->phone_number }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Used At</label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                            {{ $voucher->used_at->format('M d, Y H:i:s') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Actions</h3>
                <div class="flex flex-wrap gap-3">
                    @if($voucher->status === 'active')
                        <form method="POST" action="{{ route('isp.vouchers.deactivate', $voucher) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                    onclick="return confirm('Are you sure you want to deactivate this voucher?')">
                                Deactivate Voucher
                            </button>
                        </form>
                    @endif

                    <button onclick="copyVoucherCode('{{ $voucher->voucher_code }}')" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Copy Code
                    </button>

                    <button onclick="window.print()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyVoucherCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                // Create a temporary success message
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.classList.add('bg-green-600', 'hover:bg-green-700');
                button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                
                setTimeout(function() {
                    button.textContent = originalText;
                    button.classList.remove('bg-green-600', 'hover:bg-green-700');
                    button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy voucher code');
            });
        }
    </script>
@endsection
