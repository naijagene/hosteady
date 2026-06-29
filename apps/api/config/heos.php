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
            'channels' => ['in_app', 'email', 'sms', 'push', 'whatsapp', 'slack', 'teams', 'webhook', 'log_email'],
            'default_channels' => ['in_app'],
            'digest_frequencies' => ['daily', 'weekly'],
            'quiet_hours_enabled' => env('HEOS_NOTIFICATIONS_QUIET_HOURS', false),
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
        'workflow_marketplace' => [
            'enabled' => env('HEOS_WORKFLOW_MARKETPLACE_ENABLED', true),
        ],
        'business_modules' => [
            'enabled' => env('HEOS_BUSINESS_MODULES_ENABLED', true),
        ],
        'entities' => [
            'enabled' => env('HEOS_ENTITIES_ENABLED', true),
        ],
        'forms' => [
            'enabled' => env('HEOS_FORMS_ENABLED', true),
        ],
        'tables' => [
            'enabled' => env('HEOS_TABLES_ENABLED', true),
        ],
        'dashboards' => [
            'enabled' => env('HEOS_DASHBOARDS_ENABLED', true),
        ],
        'reports' => [
            'enabled' => env('HEOS_REPORTS_ENABLED', true),
        ],
        'data_repository' => [
            'enabled' => env('HEOS_DATA_REPOSITORY_ENABLED', true),
        ],
        'documents' => [
            'enabled' => env('HEOS_DOCUMENTS_ENABLED', true),
            'quota_bytes' => (int) env('HEOS_DOCUMENTS_QUOTA_BYTES', 5_368_709_120),
        ],
        'business_rules' => [
            'enabled' => env('HEOS_BUSINESS_RULES_ENABLED', true),
        ],
        'integrations' => [
            'enabled' => env('HEOS_INTEGRATIONS_ENABLED', true),
        ],
        'application_runtime' => [
            'enabled' => env('HEOS_APPLICATION_RUNTIME_ENABLED', true),
        ],
        'ui_metadata' => [
            'enabled' => env('HEOS_UI_METADATA_ENABLED', true),
        ],
        'navigation_designer' => [
            'enabled' => env('HEOS_NAVIGATION_DESIGNER_ENABLED', true),
        ],
        'themes' => [
            'enabled' => env('HEOS_THEMES_ENABLED', true),
        ],
        'personalization' => [
            'enabled' => env('HEOS_PERSONALIZATION_ENABLED', true),
            'recent_max' => (int) env('HEOS_PERSONALIZATION_RECENT_MAX', 50),
            'onboarding_flows' => [
                'welcome' => ['start', 'profile', 'complete'],
            ],
        ],
    ],
];
