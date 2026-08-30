<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-seo-meta
            title="Documentation · {{ config('app.name', 'PrimeBilling') }}"
            description="Complete guide to {{ config('app.name', 'PrimeBilling') }} — installation, MikroTik setup, M-Pesa payments, API reference, and troubleshooting for ISPs."
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
                        <a href="{{ route('documentation') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition font-semibold">Documentation</a>
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
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <h1 class="text-4xl font-bold mb-4">Documentation</h1>
                    <p class="text-xl text-gray-600 dark:text-gray-400">
                        Complete guide to using the ISP Billing System
                    </p>
                </div>

                <!-- Table of Contents -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 mb-8">
                    <h2 class="text-2xl font-semibold mb-4">Table of Contents</h2>
                    <ul class="space-y-2">
                        <li><a href="#getting-started" class="text-blue-600 dark:text-blue-400 hover:underline">1. Getting Started</a></li>
                        <li><a href="#installation" class="text-blue-600 dark:text-blue-400 hover:underline">2. Installation</a></li>
                        <li><a href="#configuration" class="text-blue-600 dark:text-blue-400 hover:underline">3. Configuration</a></li>
                        <li><a href="#mikrotik-setup" class="text-blue-600 dark:text-blue-400 hover:underline">4. MikroTik Router Setup</a></li>
                        <li><a href="#payment-gateways" class="text-blue-600 dark:text-blue-400 hover:underline">5. Payment Gateway Setup</a></li>
                        <li><a href="#usage" class="text-blue-600 dark:text-blue-400 hover:underline">6. Usage Guide</a></li>
                        <li><a href="#api" class="text-blue-600 dark:text-blue-400 hover:underline">7. API Reference</a></li>
                        <li><a href="#troubleshooting" class="text-blue-600 dark:text-blue-400 hover:underline">8. Troubleshooting</a></li>
                    </ul>
                </div>

                <div class="prose prose-lg dark:prose-invert max-w-none space-y-12">
                    <!-- Getting Started -->
                    <section id="getting-started" class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm">
                        <h2 class="text-3xl font-bold mb-4">1. Getting Started</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            The ISP Billing System is a comprehensive multi-tenant billing and subscriber management platform designed specifically for Internet Service Providers. It automates billing, payment processing, and router management.
                        </p>
                        <h3 class="text-2xl font-semibold mb-3 mt-6">Key Features</h3>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Multi-tenant architecture with complete data isolation</li>
                            <li>Automated billing and invoice generation</li>
                            <li>MikroTik RouterOS integration for automated user management</li>
                            <li>Multiple payment gateway support (MPesa, Selcom, Manual)</li>
                            <li>Hotspot and PPPoE user management</li>
                            <li>Comprehensive reporting and analytics</li>
                            <li>Role-based access control</li>
                        </ul>
                    </section>

                    <!-- Installation -->
                    <section id="installation" class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm">
                        <h2 class="text-3xl font-bold mb-4">2. Installation</h2>
                        <h3 class="text-2xl font-semibold mb-3 mt-6">Prerequisites</h3>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>PHP 8.2 or higher</li>
                            <li>Composer</li>
                            <li>Node.js 18+ and NPM</li>
                            <li>MikroTik RouterOS (for router integration)</li>
                            <li>MySQL/PostgreSQL or SQLite</li>
                        </ul>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Step 1: Install Dependencies</h3>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 mb-4">
                            <code class="text-green-400">
                                composer install<br>
                                npm install
                            </code>
                        </div>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Step 2: Environment Configuration</h3>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 mb-4">
                            <code class="text-green-400">
                                cp .env.example .env<br>
                                php artisan key:generate
                            </code>
                        </div>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Step 3: Database Setup</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            For SQLite (default):
                        </p>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 mb-4">
                            <code class="text-green-400">touch database/database.sqlite</code>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Or configure MySQL/PostgreSQL in your <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">.env</code> file.
                        </p>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Step 4: Run Migrations</h3>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 mb-4">
                            <code class="text-green-400">php artisan migrate</code>
                        </div>
                    </section>

                    <!-- Configuration -->
                    <section id="configuration" class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm">
                        <h2 class="text-3xl font-bold mb-4">3. Configuration</h2>
                        <h3 class="text-2xl font-semibold mb-3 mt-6">Environment Variables</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Configure the following in your <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">.env</code> file:
                        </p>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 mb-4 overflow-x-auto">
                            <pre class="text-green-400 text-sm"><code># Application
APP_NAME="ISP Billing"
APP_ENV=local
APP_DEBUG=true

# Database
DB_CONNECTION=sqlite
# Or MySQL/PostgreSQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=isp_billing
# DB_USERNAME=root
# DB_PASSWORD=

# MPesa (Optional - can be set per ISP)
MPESA_CONSUMER_KEY=your_key
MPESA_CONSUMER_SECRET=your_secret
MPESA_PASSKEY=your_passkey
MPESA_SHORTCODE=your_shortcode
MPESA_ENVIRONMENT=sandbox

# Selcom (Optional - can be set per ISP)
SELCOM_API_KEY=your_key
SELCOM_API_SECRET=your_secret
SELCOM_VENDOR_ID=your_vendor_id
SELCOM_ENVIRONMENT=sandbox

# SMS (Vonage/Nexmo - Optional)
VONAGE_KEY=your_key
VONAGE_SECRET=your_secret</code></pre>
                        </div>
                    </section>

                    <!-- MikroTik Setup -->
                    <section id="mikrotik-setup" class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm">
                        <h2 class="text-3xl font-bold mb-4">4. MikroTik Router Setup</h2>
                        <h3 class="text-2xl font-semibold mb-3 mt-6">Enable API Access</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Connect to your MikroTik router via Winbox or SSH and run:
                        </p>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 mb-4">
                            <code class="text-green-400">
                                /ip service enable api<br>
                                /ip service set api port=8728
                            </code>
                        </div>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Create API User (Recommended)</h3>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 mb-4">
                            <code class="text-green-400">
                                /user add name=api-user password=strong-password group=full
                            </code>
                        </div>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Configure IP Binding (Security)</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Restrict API access to specific IP addresses:
                        </p>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 mb-4">
                            <code class="text-green-400">
                                /ip service set api address=192.168.1.0/24
                            </code>
                        </div>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Adding Router to System</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            In the ISP dashboard, navigate to Routers and add your router with:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Router name</li>
                            <li>IP address</li>
                            <li>API username and password</li>
                            <li>Port (default: 8728)</li>
                            <li>SSL option (if using secure API)</li>
                        </ul>
                    </section>

                    <!-- Payment Gateways -->
                    <section id="payment-gateways" class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm">
                        <h2 class="text-3xl font-bold mb-4">5. Payment Gateway Setup</h2>
                        
                        <h3 class="text-2xl font-semibold mb-3 mt-6">MPesa Configuration</h3>
                        <ol class="list-decimal pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Register at <a href="https://developer.safaricom.co.ke/" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">Safaricom Developer Portal</a></li>
                            <li>Create an app to get Consumer Key and Consumer Secret</li>
                            <li>Get your Passkey from Lipa na MPesa Online account</li>
                            <li>Configure in ISP Settings → Payment Gateways or in <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">.env</code></li>
                        </ol>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Selcom Configuration</h3>
                        <ol class="list-decimal pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Register at <a href="https://developer.selcommobile.com/" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">Selcom Developer Portal</a></li>
                            <li>Get API Key, API Secret, and Vendor ID from your dashboard</li>
                            <li>Configure in ISP Settings → Payment Gateways or in <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">.env</code></li>
                        </ol>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Webhook Configuration</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Set up webhook URLs in your payment gateway dashboards:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li><strong>MPesa:</strong> <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ url('/api/payments/mpesa/callback') }}</code></li>
                            <li><strong>Selcom:</strong> <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ url('/api/payments/selcom/callback') }}</code></li>
                        </ul>
                    </section>

                    <!-- Usage Guide -->
                    <section id="usage" class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm">
                        <h2 class="text-3xl font-bold mb-4">6. Usage Guide</h2>
                        
                        <h3 class="text-2xl font-semibold mb-3 mt-6">Creating Your First ISP</h3>
                        <ol class="list-decimal pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Log in as Super Admin</li>
                            <li>Navigate to Admin → ISPs</li>
                            <li>Click "Create ISP"</li>
                            <li>Fill in ISP details (name, email, phone, address)</li>
                            <li>Configure payment gateway credentials (optional)</li>
                            <li>Save the ISP</li>
                        </ol>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Setting Up Routers</h3>
                        <ol class="list-decimal pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Log in as ISP Admin</li>
                            <li>Go to Routers → Add Router</li>
                            <li>Enter router details (name, IP, credentials)</li>
                            <li>Test connection to verify access</li>
                            <li>Save the router</li>
                        </ol>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Creating Plans</h3>
                        <ol class="list-decimal pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Navigate to Plans → Create Plan</li>
                            <li>Set plan name, price, and duration</li>
                            <li>Choose access type (Hotspot or PPPoE)</li>
                            <li>Configure speed limits (upload/download)</li>
                            <li>Set billing type (time, data, or unlimited)</li>
                            <li>Enter MikroTik speed profile name</li>
                            <li>Save the plan</li>
                        </ol>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Adding Customers</h3>
                        <ol class="list-decimal pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Go to Customers → Add Customer</li>
                            <li>Fill in customer information</li>
                            <li>Select router and plan</li>
                            <li>Choose connection type (Hotspot/PPPoE)</li>
                            <li>System automatically creates user on router</li>
                            <li>Customer is activated and subscription created</li>
                        </ol>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Processing Payments</h3>
                        <ol class="list-decimal pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Navigate to Invoices</li>
                            <li>Select an invoice</li>
                            <li>Click "Initiate Payment"</li>
                            <li>Choose payment method (MPesa/Selcom/Manual)</li>
                            <li>For MPesa: Enter phone number and confirm</li>
                            <li>Payment is processed automatically</li>
                            <li>Customer is activated upon successful payment</li>
                        </ol>
                    </section>

                    <!-- API Reference -->
                    <section id="api" class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm">
                        <h2 class="text-3xl font-bold mb-4">7. API Reference</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            The system provides RESTful API endpoints for programmatic access. All API endpoints require authentication using Laravel Sanctum.
                        </p>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Authentication</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Include the authentication token in the Authorization header:
                        </p>
                        <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 mb-4">
                            <code class="text-green-400">Authorization: Bearer {your-token}</code>
                        </div>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Customer Endpoints</h3>
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-4">
                            <code class="text-gray-800 dark:text-gray-200">
                                GET /api/isp/customers - List all customers<br>
                                POST /api/isp/customers - Create customer<br>
                                GET /api/isp/customers/{id} - Get customer details<br>
                                PUT /api/isp/customers/{id} - Update customer<br>
                                DELETE /api/isp/customers/{id} - Delete customer<br>
                                POST /api/isp/customers/{id}/suspend - Suspend customer<br>
                                POST /api/isp/customers/{id}/activate - Activate customer
                            </code>
                        </div>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Payment Endpoints</h3>
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-4">
                            <code class="text-gray-800 dark:text-gray-200">
                                POST /api/payments/initiate - Initiate payment<br>
                                POST /api/payments/mpesa/callback - MPesa webhook (public)<br>
                                POST /api/payments/selcom/callback - Selcom webhook (public)<br>
                                POST /api/payments/{payment}/confirm-manual - Confirm manual payment
                            </code>
                        </div>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Billing Endpoints</h3>
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-4">
                            <code class="text-gray-800 dark:text-gray-200">
                                GET /api/isp/billing/invoices - List invoices<br>
                                GET /api/isp/billing/payments - List payments<br>
                                POST /api/isp/billing/generate-invoices - Generate invoices<br>
                                GET /api/isp/billing/stats - Get billing statistics
                            </code>
                        </div>
                    </section>

                    <!-- Troubleshooting -->
                    <section id="troubleshooting" class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-sm">
                        <h2 class="text-3xl font-bold mb-4">8. Troubleshooting</h2>
                        
                        <h3 class="text-2xl font-semibold mb-3 mt-6">Router Connection Issues</h3>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Verify router IP address and port are correct</li>
                            <li>Check that API service is enabled on router</li>
                            <li>Ensure firewall allows connections on API port</li>
                            <li>Verify username and password are correct</li>
                            <li>Check router logs for connection attempts</li>
                        </ul>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Payment Gateway Issues</h3>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Verify API credentials are correct</li>
                            <li>Check that webhook URLs are accessible from internet</li>
                            <li>Ensure webhook signatures are being validated</li>
                            <li>Check payment gateway dashboard for transaction logs</li>
                            <li>Verify environment (sandbox vs production) matches</li>
                        </ul>

                        <h3 class="text-2xl font-semibold mb-3 mt-6">Common Issues</h3>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-xl font-semibold mb-2">Users not created on router</h4>
                                <p class="text-gray-700 dark:text-gray-300">
                                    Check router connection, verify speed profile exists on router, check router logs.
                                </p>
                            </div>
                            <div>
                                <h4 class="text-xl font-semibold mb-2">Invoices not generating</h4>
                                <p class="text-gray-700 dark:text-gray-300">
                                    Ensure scheduled jobs are running, check subscription end dates, verify invoice generation settings.
                                </p>
                            </div>
                            <div>
                                <h4 class="text-xl font-semibold mb-2">Payments not processing</h4>
                                <p class="text-gray-700 dark:text-gray-300">
                                    Verify payment gateway credentials, check webhook configuration, review payment logs.
                                </p>
                            </div>
                        </div>
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
                            <li><a href="{{ route('documentation') }}" class="hover:text-white transition font-semibold">Documentation</a></li>
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





