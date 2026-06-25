<?php

namespace App\Services\Application;

use App\Models\Application;
use App\Services\Application\Data\ApplicationManifest;

class ApplicationManifestService
{
    public function __construct(
        private readonly ApplicationSettingsRegistry $settingsRegistry,
    ) {
    }

    public function manifestForApplication(Application $application): ApplicationManifest
    {
        return new ApplicationManifest(
            applicationPublicId: $application->public_id,
            key: $application->key,
            name: $application->name,
            catalogVersion: $application->version,
            capabilities: $this->normalizeStringList($application->capabilities),
            dependencies: $this->normalizeStringList($application->dependencies),
            settingDefinitions: $this->settingsRegistry
                ->workspaceDefinitionsForApplication($application->id)
                ->values()
                ->all(),
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map('strval', $value));
    }
}
