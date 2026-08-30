<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provisioning snapshot cache
    |--------------------------------------------------------------------------
    |
    | Snapshots are stored in Redis when available (falls back to default store).
    | Refresh interval is driven by the scheduler — see routes/console.php.
    |
    */
    'cache_store' => env('PROVISIONING_CACHE_STORE', env('CACHE_STORE', 'database')),

    'snapshot_ttl_seconds' => (int) env('PROVISIONING_SNAPSHOT_TTL', 120),

    'refresh_interval_seconds' => (int) env('PROVISIONING_REFRESH_INTERVAL', 45),

    'queue_saturation_threshold' => (float) env('PROVISIONING_QUEUE_SATURATION', 0.90),

];
