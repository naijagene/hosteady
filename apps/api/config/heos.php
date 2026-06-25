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
            'reserved' => true,
            'description' => 'Module and platform diagnostics (reserved for a future slice).',
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
];
