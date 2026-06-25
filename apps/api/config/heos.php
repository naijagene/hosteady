<?php

return [
    'runtime_cache' => [
        'enabled' => env('HEOS_RUNTIME_CACHE_ENABLED', true),
        'ttl' => (int) env('HEOS_RUNTIME_CACHE_TTL', 300),
        'store' => env('HEOS_RUNTIME_CACHE_STORE'),
        'schema_version' => 1,
        'lock_seconds' => (int) env('HEOS_RUNTIME_CACHE_LOCK_SECONDS', 10),
        'lock_wait_seconds' => (int) env('HEOS_RUNTIME_CACHE_LOCK_WAIT_SECONDS', 5),
    ],
];
