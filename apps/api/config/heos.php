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
        'files' => [
            'enabled' => env('HEOS_FILES_ENABLED', true),
            'default_disk' => env('HEOS_FILES_DISK', 'local'),
            'public_disk' => env('HEOS_FILES_PUBLIC_DISK', 'public'),
            'max_upload_size' => (int) env('HEOS_FILES_MAX_UPLOAD_SIZE', 10485760),
            'quota_bytes' => (int) env('HEOS_FILES_QUOTA_BYTES', 1073741824),
            'allowed_mime_types' => [
                'image/png',
                'image/jpeg',
                'image/gif',
                'image/webp',
                'application/pdf',
                'text/plain',
                'text/csv',
                'application/json',
            ],
            'visibility_modes' => ['private', 'workspace', 'organization', 'public'],
        ],
        'jobs' => [
            'enabled' => env('HEOS_JOBS_ENABLED', true),
            'default_queue' => env('HEOS_JOBS_QUEUE', 'default'),
            'max_attempts' => (int) env('HEOS_JOBS_MAX_ATTEMPTS', 3),
        ],
        'scheduler' => [
            'enabled' => env('HEOS_SCHEDULER_ENABLED', true),
        ],
        'search' => [
            'enabled' => env('HEOS_SEARCH_ENABLED', true),
            'max_results' => (int) env('HEOS_SEARCH_MAX_RESULTS', 50),
            'recent_limit' => (int) env('HEOS_SEARCH_RECENT_LIMIT', 10),
        ],
        'workflow' => [
            'enabled' => env('HEOS_WORKFLOW_ENABLED', true),
        ],
        'human_tasks' => [
            'enabled' => env('HEOS_HUMAN_TASKS_ENABLED', true),
        ],
        'approvals' => [
            'enabled' => env('HEOS_APPROVALS_ENABLED', true),
        ],
        'automation' => [
            'enabled' => env('HEOS_AUTOMATION_ENABLED', true),
        ],
        'workflow_designer' => [
            'enabled' => env('HEOS_WORKFLOW_DESIGNER_ENABLED', true),
        ],
    ],
];
