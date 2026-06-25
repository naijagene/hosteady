<?php

namespace App\Services\Module\Data;

readonly class ModuleInspectionResult
{
    /**
     * @param  list<string>  $dependencies
     * @param  list<string>  $capabilities
     * @param  list<string>  $permissions
     * @param  list<string>  $settings
     */
    public function __construct(
        public string $moduleKey,
        public string $moduleUuid,
        public string $name,
        public string $version,
        public int $manifestVersion,
        public array $dependencies,
        public array $capabilities,
        public array $permissions,
        public array $settings,
        public bool $lifecycleSupported,
        public bool $runtimeContributor,
        public bool $syncSupported,
        public string $healthStatus,
        public ?string $healthMessage = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'module_uuid' => $this->moduleUuid,
            'name' => $this->name,
            'version' => $this->version,
            'manifest_version' => $this->manifestVersion,
            'dependencies' => $this->dependencies,
            'capabilities' => $this->capabilities,
            'permissions' => $this->permissions,
            'settings' => $this->settings,
            'lifecycle_supported' => $this->lifecycleSupported,
            'runtime_contributor' => $this->runtimeContributor,
            'sync_supported' => $this->syncSupported,
            'health_status' => $this->healthStatus,
            'health_message' => $this->healthMessage,
        ];
    }
}
