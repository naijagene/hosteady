<?php

namespace App\Services\Application;

use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Support\Tenant\TenantContext;

class ApplicationLoaderService
{
    /**
     * @param  list<ApplicationDefinition>  $enabled
     * @param  array<string, mixed>  $discovered
     * @return array<string, mixed>
     */
    public function aggregateCapabilities(TenantContext $context, array $enabled, array $discovered): array
    {
        $capabilities = [];

        foreach ($enabled as $application) {
            $manifestCapabilities = is_array($application->manifest['capabilities'] ?? null)
                ? $application->manifest['capabilities']
                : [];
            $capabilities = array_merge($capabilities, $manifestCapabilities);
        }

        foreach ($discovered as $domain => $items) {
            if (is_array($items) && $items !== []) {
                $capabilities[$domain] = true;
            }
        }

        return $capabilities;
    }

    /**
     * @param  list<ApplicationDefinition>  $enabled
     * @return array<string, mixed>
     */
    public function workspaceMetadata(TenantContext $context, array $enabled): array
    {
        return [
            'workspace_public_id' => $context->workspacePublicId,
            'enabled_application_keys' => array_map(fn (ApplicationDefinition $app) => $app->applicationKey, $enabled),
        ];
    }
}
