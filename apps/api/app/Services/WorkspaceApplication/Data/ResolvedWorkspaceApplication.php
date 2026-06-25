<?php

namespace App\Services\WorkspaceApplication\Data;

readonly class ResolvedWorkspaceApplication
{
    /**
     * @param  array<string, RuntimeSettingValue>  $settings
     * @param  list<string>  $dependencies
     */
    public function __construct(
        public string $workspaceApplicationPublicId,
        public string $organizationApplicationPublicId,
        public string $applicationPublicId,
        public string $key,
        public string $name,
        public string $catalogVersion,
        public string $enabledVersion,
        public bool $isBootstrap,
        public array $settings,
        public array $dependencies = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $settings = [];

        foreach ($this->settings as $settingKey => $setting) {
            $settings[$settingKey] = $setting->toArray();
        }

        return [
            'workspace_application_public_id' => $this->workspaceApplicationPublicId,
            'organization_application_public_id' => $this->organizationApplicationPublicId,
            'application_public_id' => $this->applicationPublicId,
            'key' => $this->key,
            'name' => $this->name,
            'catalog_version' => $this->catalogVersion,
            'enabled_version' => $this->enabledVersion,
            'is_bootstrap' => $this->isBootstrap,
            'settings' => $settings,
            'dependencies' => $this->dependencies,
        ];
    }
}
