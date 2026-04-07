<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Privacy Policy - {{ config('app.name', 'ISP Billing') }}</title>
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
                <h1 class="text-4xl font-bold mb-4">Privacy Policy</h1>
                <p class="text-gray-600 dark:text-gray-400 mb-8">Last updated: {{ date('F j, Y') }}</p>

                <div class="prose prose-lg dark:prose-invert max-w-none">
                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">1. Introduction</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            ISP Billing System ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our Service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">2. Information We Collect</h2>
                        <h3 class="text-xl font-semibold mb-3 mt-4">2.1 Information You Provide</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We collect information that you provide directly to us, including:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Account registration information (name, email, phone number)</li>
                            <li>Payment information (processed through secure third-party gateways)</li>
                            <li>Customer and billing data you enter into the system</li>
                            <li>Communication data when you contact us for support</li>
                        </ul>

                        <h3 class="text-xl font-semibold mb-3 mt-4">2.2 Automatically Collected Information</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We automatically collect certain information when you use our Service:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Log data (IP address, browser type, access times)</li>
                            <li>Device information</li>
                            <li>Usage patterns and preferences</li>
                            <li>Cookies and similar tracking technologies</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">3. How We Use Your Information</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We use the information we collect to:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li>Provide, maintain, and improve our Service</li>
                            <li>Process transactions and send related information</li>
                            <li>Send technical notices, updates, and support messages</li>
                            <li>Respond to your comments, questions, and requests</li>
                            <li>Monitor and analyze trends, usage, and activities</li>
                            <li>Detect, prevent, and address technical issues</li>
                            <li>Comply with legal obligations</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">4. Data Sharing and Disclosure</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We do not sell your personal information. We may share your information only in the following circumstances:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li><strong>Service Providers:</strong> With third-party vendors who perform services on our behalf (payment processing, hosting, analytics)</li>
                            <li><strong>Legal Requirements:</strong> When required by law or to protect our rights</li>
                            <li><strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets</li>
                            <li><strong>With Your Consent:</strong> When you have given us explicit permission</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">5. Data Security</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the Internet or electronic storage is 100% secure.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">6. Data Retention</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We retain your personal information for as long as necessary to fulfill the purposes outlined in this Privacy Policy, unless a longer retention period is required or permitted by law. When we no longer need your information, we will securely delete or anonymize it.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">7. Your Rights</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Depending on your location, you may have the following rights regarding your personal information:
                        </p>
                        <ul class="list-disc pl-6 mb-4 text-gray-700 dark:text-gray-300 space-y-2">
                            <li><strong>Access:</strong> Request access to your personal information</li>
                            <li><strong>Correction:</strong> Request correction of inaccurate information</li>
                            <li><strong>Deletion:</strong> Request deletion of your personal information</li>
                            <li><strong>Portability:</strong> Request transfer of your data to another service</li>
                            <li><strong>Objection:</strong> Object to processing of your personal information</li>
                            <li><strong>Restriction:</strong> Request restriction of processing</li>
                        </ul>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            To exercise these rights, please contact us using the information provided below.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">8. Cookies and Tracking Technologies</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We use cookies and similar tracking technologies to track activity on our Service and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our Service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">9. Third-Party Services</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Our Service may contain links to third-party websites or services. We are not responsible for the privacy practices of these third parties. We encourage you to read their privacy policies.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">10. Children's Privacy</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Our Service is not intended for children under the age of 18. We do not knowingly collect personal information from children. If you are a parent or guardian and believe your child has provided us with personal information, please contact us.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">11. International Data Transfers</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Your information may be transferred to and maintained on computers located outside of your state, province, country, or other governmental jurisdiction where data protection laws may differ. By using our Service, you consent to the transfer of your information to these facilities.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">12. Changes to This Privacy Policy</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date. You are advised to review this Privacy Policy periodically for any changes.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-2xl font-semibold mb-4">13. Contact Us</h2>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            If you have any questions about this Privacy Policy, please contact us at:
                        </p>
                        <p class="text-gray-700 dark:text-gray-300">
                            Email: privacy@ispbilling.com<br>
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





