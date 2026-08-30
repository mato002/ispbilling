<?php

return [

    /** Platform SMS unit cost charged per outbound message (KES). */
    'unit_cost' => (float) env('SMS_UNIT_COST', 0.60),

    'currency_symbols' => [
        'KES' => 'KSh',
        'USD' => '$',
        'EUR' => '€',
    ],

    /** Wallet balance below this amount (KES) triggers low-balance UI warnings. */
    'low_balance_threshold' => (float) env('SMS_LOW_BALANCE_THRESHOLD', 50.00),

    /** Outbound campaign worker batch size. */
    'campaign_batch_size' => (int) env('SMS_CAMPAIGN_BATCH_SIZE', 25),

    /** Milliseconds to pause between SMS sends (gateway rate limiting). */
    'campaign_rate_limit_ms' => (int) env('SMS_CAMPAIGN_RATE_LIMIT_MS', 100),

    /**
     * Active root SMS socket driver (overrides platform_integrations when set).
     * Supported: africastalking, onfon
     */
    'platform_driver' => env('SMS_PLATFORM_DRIVER'),

    'driver_labels' => [
        'africastalking' => "Africa's Talking",
        'onfon' => 'Onfon Media',
        'twilio' => 'Twilio',
        'none' => 'Not configured',
    ],

];
