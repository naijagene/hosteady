<?php

namespace App\Services\Runtime;

use App\Services\WorkspaceApplication\Data\ResolvedWorkspaceApplication;
use App\Services\WorkspaceApplication\Data\RuntimeMembershipSnapshot;
use App\Services\WorkspaceApplication\Data\RuntimeOrganizationSnapshot;
use App\Services\WorkspaceApplication\Data\RuntimeSettingValue;
use App\Services\WorkspaceApplication\Data\RuntimeWorkspaceSnapshot;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;

class RuntimeSnapshotSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(WorkspaceRuntimeContext $context): array
    {
        return [
            'organization' => $context->organization->toArray(),
            'workspace' => $context->workspace->toArray(),
            'membership' => $context->membership->toArray(),
            'active_applications' => array_map(
                fn (ResolvedWorkspaceApplication $application) => $this->serializeApplication($application),
                $context->activeApplications,
            ),
            'active_application' => $context->activeApplication === null
                ? null
                : $this->serializeApplication($context->activeApplication),
            'runtime_version' => $context->runtimeVersion,
            'settings_version' => $context->settingsVersion,
            'runtime_metadata' => $context->runtimeMetadata,
            'capabilities' => $context->capabilities,
        ];
    }

    public function deserialize(array $payload): WorkspaceRuntimeContext
    {
        $activeApplications = array_map(
            fn (array $application) => $this->deserializeApplication($application),
            $payload['active_applications'] ?? [],
        );

        $applicationsByPublicId = [];

        foreach ($activeApplications as $application) {
            $applicationsByPublicId[$application->workspaceApplicationPublicId] = $application;
        }

        $activeApplicationPayload = $payload['active_application'] ?? null;
        $activeApplication = is_array($activeApplicationPayload)
            ? $this->deserializeApplication($activeApplicationPayload)
            : null;

        return new WorkspaceRuntimeContext(
            organization: new RuntimeOrganizationSnapshot(
                publicId: $payload['organization']['public_id'],
                name: $payload['organization']['name'],
                slug: $payload['organization']['slug'],
                status: $payload['organization']['status'],
            ),
            workspace: new RuntimeWorkspaceSnapshot(
                publicId: $payload['workspace']['public_id'],
                name: $payload['workspace']['name'],
                slug: $payload['workspace']['slug'],
                isDefault: (bool) $payload['workspace']['is_default'],
                status: $payload['workspace']['status'],
            ),
            membership: new RuntimeMembershipSnapshot(
                publicId: $payload['membership']['public_id'],
                status: $payload['membership']['status'],
            ),
            activeApplications: $activeApplications,
            activeApplication: $activeApplication,
            runtimeVersion: $payload['runtime_version'],
            settingsVersion: (int) $payload['settings_version'],
            runtimeMetadata: $payload['runtime_metadata'],
            capabilities: $payload['capabilities'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeApplication(ResolvedWorkspaceApplication $application): array
    {
        $data = $application->toArray();
        $data['capabilities'] = $application->capabilities;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $application
     */
    private function deserializeApplication(array $application): ResolvedWorkspaceApplication
    {
        $settings = [];

        foreach ($application['settings'] ?? [] as $settingKey => $setting) {
            $settings[$settingKey] = new RuntimeSettingValue(
                value: $setting['value'],
                type: $setting['type'],
                version: (int) $setting['version'],
                isSensitive: (bool) $setting['is_sensitive'],
                valueRedacted: (bool) $setting['value_redacted'],
                isDefault: (bool) ($setting['is_default'] ?? false),
                definitionPublicId: $setting['definition_public_id'] ?? null,
                label: $setting['label'] ?? null,
                category: $setting['category'] ?? null,
            );
        }

        return new ResolvedWorkspaceApplication(
            workspaceApplicationPublicId: $application['workspace_application_public_id'],
            organizationApplicationPublicId: $application['organization_application_public_id'],
            applicationPublicId: $application['application_public_id'],
            key: $application['key'],
            name: $application['name'],
            catalogVersion: $application['catalog_version'],
            enabledVersion: $application['enabled_version'],
            isBootstrap: (bool) $application['is_bootstrap'],
            settings: $settings,
            capabilities: $application['capabilities'] ?? [],
            dependencies: $application['dependencies'] ?? [],
        );
    }
}
