<?php

return [

    'batch_size' => (int) env('ROUTER_MIGRATION_BATCH_SIZE', 25),

    'api_delay_ms' => (int) env('ROUTER_MIGRATION_API_DELAY_MS', 300),

    'max_attempts' => (int) env('ROUTER_MIGRATION_MAX_ATTEMPTS', 3),

    'chunk_redispatch_delay_seconds' => (int) env('ROUTER_MIGRATION_CHUNK_DELAY', 5),

];
