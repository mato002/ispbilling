@extends('hotspot.layout')

@section('content')
<div>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Login to WiFi</h2>
        <p class="text-sm text-gray-600">Enter your credentials to connect</p>
    </div>

    <form method="POST" action="{{ route('hotspot.authenticate') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="router_ip" value="{{ request()->get('router_ip') ?? request()->ip() }}">

        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                Username
            </label>
            <input id="username" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                   type="text" 
                   name="username" 
                   value="{{ old('username') }}" 
                   required 
                   autofocus 
                   placeholder="Enter your username">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                Password
            </label>
            <input id="password" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                   type="password" 
                   name="password" 
                   required 
                   placeholder="Enter your password">
        </div>

        <div>
            <button type="submit" 
                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg shadow-lg hover:from-blue-700 hover:to-indigo-700 transition duration-150">
                Connect to WiFi
            </button>
        </div>
    </form>

    <div class="mt-6 text-center space-y-2">
        <a href="{{ route('hotspot.redeem', ['router_ip' => request()->get('router_ip') ?? request()->ip()]) }}" 
           class="block text-sm text-blue-600 hover:text-blue-700 font-medium">
            Have a voucher? Redeem it here
        </a>
        <a href="{{ route('hotspot.splash', ['router_ip' => request()->get('router_ip') ?? request()->ip()]) }}" 
           class="block text-sm text-gray-600 hover:text-gray-700">
            ← Back to Home
        </a>
    </div>
</div>
@endsection





