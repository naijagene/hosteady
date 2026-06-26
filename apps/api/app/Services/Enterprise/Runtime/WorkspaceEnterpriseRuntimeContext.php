<?php

namespace App\Services\Enterprise\Runtime;

use App\Modules\Sdk\Enterprise\Contracts\EnterpriseRuntimeContext;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;

readonly class WorkspaceEnterpriseRuntimeContext implements EnterpriseRuntimeContext
{
    public function __construct(
        private WorkspaceRuntimeContext $runtime,
    ) {
    }

    public function runtimeVersion(): string
    {
        return $this->runtime->runtimeVersion;
    }

    public function capabilityEnabled(string $capability): bool
    {
        return (bool) ($this->runtime->capabilities[$capability] ?? false);
    }

    public function featureFlag(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->runtime->featureFlags)
            ? $this->runtime->featureFlags[$key]
            : $default;
    }

    public function moduleMetadata(string $moduleKey): array
    {
        $metadata = $this->runtime->runtimeMetadata[$moduleKey] ?? [];

        return is_array($metadata) ? $metadata : [];
    }

    public function enterpriseMetadata(): array
    {
        return is_array($this->runtime->runtimeMetadata['enterprise'] ?? null)
            ? $this->runtime->runtimeMetadata['enterprise']
            : [];
    }
}
