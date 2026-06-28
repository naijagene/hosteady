<?php

namespace App\Services\Application;

use App\Modules\Sdk\Application\Contracts\ApplicationManifestProvider;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Modules\Sdk\Application\Data\ApplicationManifest as RuntimeApplicationManifest;

class ApplicationRuntimeManifestService implements ApplicationManifestProvider
{
    public function manifest(string $applicationKey): RuntimeApplicationManifest
    {
        return new RuntimeApplicationManifest(
            applicationKey: $applicationKey,
            name: $applicationKey,
            version: '1.0.0',
            type: 'business',
            capabilities: [],
            dependencies: [],
            navigation: [],
            metadata: [],
        );
    }

    public function fromDefinition(ApplicationDefinition $definition): RuntimeApplicationManifest
    {
        $manifest = $definition->manifest;

        return RuntimeApplicationManifest::fromArray(array_merge([
            'application_key' => $definition->applicationKey,
            'name' => $definition->name,
            'version' => '1.0.0',
            'type' => $definition->applicationType,
            'capabilities' => [],
            'dependencies' => [],
            'navigation' => [],
            'metadata' => [],
        ], $manifest));
    }
}
