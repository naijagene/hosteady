<?php

return [
    'module_providers' => [
        App\Modules\Core\CoreModuleServiceProvider::class,
        App\Modules\Workspace\WorkspaceModuleServiceProvider::class,
        App\Modules\Demo\DemoModuleServiceProvider::class,
    ],

    'sync' => [
        'on_seed' => env('HEOS_MODULE_SYNC_ON_SEED', true),
    ],

    'commands' => [
        'doctor' => [
            'name' => 'heos:doctor',
            'reserved' => false,
            'description' => 'Run HEOS platform and module diagnostics.',
        ],
    ],

    'runtime_cache' => [
        'enabled' => env('HEOS_RUNTIME_CACHE_ENABLED', true),
        'ttl' => (int) env('HEOS_RUNTIME_CACHE_TTL', 300),
        'store' => env('HEOS_RUNTIME_CACHE_STORE'),
        'schema_version' => 1,
        'lock_seconds' => (int) env('HEOS_RUNTIME_CACHE_LOCK_SECONDS', 10),
        'lock_wait_seconds' => (int) env('HEOS_RUNTIME_CACHE_LOCK_WAIT_SECONDS', 5),
    ],

    'enterprise' => [
        'runtime_aware' => env('HEOS_ENTERPRISE_RUNTIME_AWARE', true),
        'event_bus' => [
            'enabled' => env('HEOS_EVENT_BUS_ENABLED', true),
            'async' => env('HEOS_EVENT_BUS_ASYNC', false),
        ],
        'notifications' => [
            'enabled' => env('HEOS_NOTIFICATIONS_ENABLED', true),
            'channels' => ['in_app', 'log_email'],
        ],
        'reference_data' => [
            'enabled' => env('HEOS_REFERENCE_DATA_ENABLED', true),
            'cache_ttl' => (int) env('HEOS_REFERENCE_DATA_CACHE_TTL', 3600),
        ],
    ],
];
