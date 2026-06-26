<?php

namespace App\Services\Enterprise\Runtime;

use App\Modules\Sdk\Enterprise\Contracts\EnterpriseRuntimeContext;

readonly class ConfigEnterpriseRuntimeContext implements EnterpriseRuntimeContext
{
    public function runtimeVersion(): string
    {
        return 'config-fallback';
    }

    public function capabilityEnabled(string $capability): bool
    {
        return match ($capability) {
            'notifications' => (bool) config('heos.enterprise.notifications.enabled', true),
            'events' => (bool) config('heos.enterprise.event_bus.enabled', true),
            'reference_data' => (bool) config('heos.enterprise.reference_data.enabled', true),
            'storage' => (bool) config('heos.enterprise.files.enabled', true),
            'media' => (bool) config('heos.enterprise.files.enabled', true),
            'jobs' => (bool) config('heos.enterprise.jobs.enabled', true),
            'scheduler' => (bool) config('heos.enterprise.scheduler.enabled', true),
            default => false,
        };
    }

    public function featureFlag(string $key, mixed $default = null): mixed
    {
        return config('heos.enterprise.feature_flags.'.$key, $default);
    }

    public function moduleMetadata(string $moduleKey): array
    {
        return [];
    }

    public function enterpriseMetadata(): array
    {
        return [];
    }
}
