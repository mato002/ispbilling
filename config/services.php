<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    */

    'mpesa' => [
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'passkey' => env('MPESA_PASSKEY'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'environment' => env('MPESA_ENV', env('MPESA_ENVIRONMENT', 'sandbox')), // sandbox or production
        'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
        'timeout_url' => env('MPESA_TIMEOUT_URL'),
        /** Small STK amount to confirm the ISP can pay platform invoices (SaaS onboarding). */
        'saas_onboarding_verification_amount' => (float) env('MPESA_SAAS_VERIFICATION_AMOUNT', 1),
        'allowed_ips' => env('MPESA_ALLOWED_IPS') ? explode(',', env('MPESA_ALLOWED_IPS')) : [
            // Safaricom production IPs
            '196.201.214.200',
            '196.201.214.206',
            '196.201.213.114',
            '196.201.214.207',
            '196.201.214.208',
            '196.201.213.44',
            '196.201.212.127',
            '196.201.212.128',
            '196.201.212.129',
            '196.201.212.132',
            '196.201.212.136',
            '196.201.212.138',
        ],
    ],

    'selcom' => [
        'api_key' => env('SELCOM_API_KEY'),
        'api_secret' => env('SELCOM_API_SECRET'),
        'vendor_id' => env('SELCOM_VENDOR_ID'),
        'environment' => env('SELCOM_ENVIRONMENT', 'sandbox'), // sandbox or production
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

    'vonage' => [
        'key' => env('VONAGE_KEY'),
        'secret' => env('VONAGE_SECRET'),
        'sms_from' => env('VONAGE_SMS_FROM'),
    ],

];
