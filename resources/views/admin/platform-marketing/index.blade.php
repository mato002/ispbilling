<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Messaging & push') }}</h2>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">{{ session('success') }}</div>
    @endif

    <div class="mb-6 text-sm text-gray-600 dark:text-gray-400">
        Credentials are stored in the encrypted <code class="text-xs">platform_integrations.messaging_channels</code> blob. Connect your notification jobs to these keys for Twilio / Africa’s Talking SMS and Firebase Web Push (FCM).
    </div>

    <form method="POST" action="{{ route('admin.marketing.update') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6 text-sm max-w-3xl">
        @csrf
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-2">SMS provider</label>
            <select name="sms_provider" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="none" @selected(($messaging['sms_provider'] ?? 'none') === 'none')>None</option>
                <option value="twilio" @selected(($messaging['sms_provider'] ?? '') === 'twilio')>Twilio</option>
                <option value="africastalking" @selected(($messaging['sms_provider'] ?? '') === 'africastalking')>Africa’s Talking</option>
            </select>
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Twilio</h3>
            <input name="twilio_account_sid" value="{{ old('twilio_account_sid', $messaging['twilio_account_sid'] ?? '') }}" placeholder="Account SID" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            <input name="twilio_auth_token" value="{{ old('twilio_auth_token', $messaging['twilio_auth_token'] ?? '') }}" placeholder="Auth token" type="password" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            <input name="twilio_from" value="{{ old('twilio_from', $messaging['twilio_from'] ?? '') }}" placeholder="From number / sender ID" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Africa’s Talking</h3>
            <input name="africastalking_username" value="{{ old('africastalking_username', $messaging['africastalking_username'] ?? '') }}" placeholder="Username" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            <input name="africastalking_api_key" value="{{ old('africastalking_api_key', $messaging['africastalking_api_key'] ?? '') }}" placeholder="API key" type="password" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            <input name="africastalking_from" value="{{ old('africastalking_from', $messaging['africastalking_from'] ?? '') }}" placeholder="Short code / sender ID" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
        </div>

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Firebase (web / FCM client config)</h3>
            <input name="firebase_web_api_key" value="{{ old('firebase_web_api_key', $messaging['firebase_web_api_key'] ?? '') }}" placeholder="Web API key" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            <input name="firebase_project_id" value="{{ old('firebase_project_id', $messaging['firebase_project_id'] ?? '') }}" placeholder="Project ID" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            <input name="firebase_messaging_sender_id" value="{{ old('firebase_messaging_sender_id', $messaging['firebase_messaging_sender_id'] ?? '') }}" placeholder="Messaging sender ID" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            <input name="firebase_app_id" value="{{ old('firebase_app_id', $messaging['firebase_app_id'] ?? '') }}" placeholder="App ID" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            <input name="firebase_vapid_public_key" value="{{ old('firebase_vapid_public_key', $messaging['firebase_vapid_public_key'] ?? '') }}" placeholder="Web push VAPID public key" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
        </div>

        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
    </form>
</x-admin-layout>
