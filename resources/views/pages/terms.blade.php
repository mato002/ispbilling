<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo-meta
            title="Terms of Service · {{ config('app.name', 'PrimeBilling') }}"
            description="Terms of Service for {{ config('app.name', 'PrimeBilling') }} — ISP billing platform usage, accounts, payments, and service availability."
            robots="index, follow"
        />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
        <!-- Navigation -->
        <nav class="fixed top-0 w-full bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-800 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="{{ route('welcome') }}" class="text-2xl font-bold text-blue-600 dark:text-blue-400">ISP Billing</a>
                    </div>
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="{{ route('welcome') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Home</a>
                        <a href="{{ route('documentation') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Documentation</a>
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">Log in</a>
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="pt-24 pb-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-4xl font-bold mb-4">Terms of Service</h1>
                <p class="text-gray-600 dark:text-gray-400 mb-8">Last updated: {{ date('F j, Y') }}</p>

                <div class="prose prose-lg dark:prose-invert max-w-none">
                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">1. Acceptance of Terms</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            By accessing and using the ISP Billing System ("Service"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">2. Use License</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Permission is granted to temporarily use the ISP Billing System for personal or commercial purposes. This is the grant of a license, not a transfer of title, and under this license you may not:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Modify or copy the materials</li>
                            <li>Use the materials for any commercial purpose or for any public display</li>
                            <li>Attempt to reverse engineer any software contained in the ISP Billing System</li>
                            <li>Remove any copyright or other proprietary notations from the materials</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">3. Service Availability</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We strive to ensure the Service is available 24/7, but we do not guarantee uninterrupted access. The Service may be unavailable due to maintenance, updates, or circumstances beyond our control. We are not liable for any losses resulting from service unavailability.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">4. User Accounts</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            You are responsible for maintaining the confidentiality of your account credentials. You agree to:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Keep your password secure and confidential</li>
                            <li>Notify us immediately of any unauthorized use of your account</li>
                            <li>Accept responsibility for all activities that occur under your account</li>
                            <li>Provide accurate and complete information when creating an account</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">5. Payment Terms</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            If you subscribe to paid services:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>All fees are due in advance unless otherwise agreed</li>
                            <li>Payments are processed through secure third-party payment gateways</li>
                            <li>Refunds are subject to our refund policy</li>
                            <li>We reserve the right to change pricing with 30 days notice</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">6. Data and Privacy</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Your use of the Service is also governed by our Privacy Policy. Please review our Privacy Policy to understand our practices regarding data collection and use.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">7. Prohibited Uses</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            You may not use the Service:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>For any unlawful purpose or to solicit others to perform unlawful acts</li>
                            <li>To violate any international, federal, provincial, or state regulations, rules, laws, or local ordinances</li>
                            <li>To infringe upon or violate our intellectual property rights or the intellectual property rights of others</li>
                            <li>To harass, abuse, insult, harm, defame, slander, disparage, intimidate, or discriminate</li>
                            <li>To submit false or misleading information</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">8. Limitation of Liability</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            In no event shall ISP Billing System or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the Service, even if ISP Billing System or an authorized representative has been notified orally or in writing of the possibility of such damage.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">9. Termination</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We may terminate or suspend your account and access to the Service immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms. Upon termination, your right to use the Service will immediately cease.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">10. Changes to Terms</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">11. Contact Information</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            If you have any questions about these Terms of Service, please contact us at:
                        </p>
                        <p class="text-gray-700 dark:text-gray-300">
                            Email: support@ispbilling.com<br>
                            Address: [Your Company Address]
                        </p>
                    </section>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-400 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-white font-bold text-xl mb-4">ISP Billing</h3>
                        <p class="text-sm">Smart billing system for Internet Service Providers</p>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Legal</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ route('terms') }}" class="hover:text-white transition">Terms of Service</a></li>
                            <li><a href="{{ route('privacy') }}" class="hover:text-white transition">Privacy Policy</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Resources</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ route('documentation') }}" class="hover:text-white transition">Documentation</a></li>
                            <li><a href="{{ route('welcome') }}#faq" class="hover:text-white transition">FAQ</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-semibold mb-4">Company</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="{{ route('welcome') }}" class="hover:text-white transition">Home</a></li>
                            <li><a href="{{ route('welcome') }}#features" class="hover:text-white transition">Features</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                    <p>&copy; {{ date('Y') }} ISP Billing System. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </body>
</html>





