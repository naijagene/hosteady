<?php

namespace App\Services\WorkspaceApplication;

use App\Services\WorkspaceApplication\Data\ResolvedWorkspaceApplication;
use App\Services\WorkspaceApplication\Data\RuntimeSettingValue;
use App\Services\WorkspaceApplication\Data\WorkspaceApplicationRuntimeInput;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeManifestResult;
use App\Services\WorkspaceApplication\Data\WorkspaceSettingRuntimeInput;

class WorkspaceRuntimeManifestBuilder
{
    public function __construct(
        private readonly WorkspaceSettingMasker $masker,
    ) {
    }

    /**
     * @param  list<WorkspaceApplicationRuntimeInput>  $applications
     * @param  array<string, list<WorkspaceSettingRuntimeInput>>  $settingsByWorkspaceApplicationPublicId
     */
    public function build(array $applications, array $settingsByWorkspaceApplicationPublicId): WorkspaceRuntimeManifestResult
    {
        $manifestApplications = [];
        $resolvedApplications = [];
        $applicationsByPublicId = [];

        usort($applications, fn (WorkspaceApplicationRuntimeInput $left, WorkspaceApplicationRuntimeInput $right) => strcmp($left->key, $right->key));

        foreach ($applications as $application) {
            $settings = $settingsByWorkspaceApplicationPublicId[$application->workspaceApplicationPublicId] ?? [];
            usort($settings, fn (WorkspaceSettingRuntimeInput $left, WorkspaceSettingRuntimeInput $right) => strcmp($left->settingKey, $right->settingKey));

            $manifestSettings = [];
            $resolvedSettings = [];

            foreach ($settings as $setting) {
                $manifestSettings[] = [
                    'setting_key' => $setting->settingKey,
                    'version' => $setting->version,
                ];

                $resolvedSettings[$setting->settingKey] = new RuntimeSettingValue(
                    value: $this->masker->maskValue($setting->value, $setting->isSensitive),
                    type: $setting->type,
                    version: $setting->version,
                    isSensitive: $setting->isSensitive,
                    valueRedacted: $this->masker->isRedacted($setting->isSensitive),
                );
            }

            $manifestApplications[] = [
                'key' => $application->key,
                'workspace_application_status' => $application->workspaceApplicationStatus,
                'organization_application_status' => $application->organizationApplicationStatus,
                'catalog_application_status' => $application->catalogApplicationStatus,
                'enabled_version' => $application->enabledVersion,
                'catalog_version' => $application->catalogVersion,
                'settings' => $manifestSettings,
            ];

            $resolved = new ResolvedWorkspaceApplication(
                workspaceApplicationPublicId: $application->workspaceApplicationPublicId,
                organizationApplicationPublicId: $application->organizationApplicationPublicId,
                applicationPublicId: $application->applicationPublicId,
                key: $application->key,
                name: $application->name,
                catalogVersion: $application->catalogVersion,
                enabledVersion: $application->enabledVersion,
                isBootstrap: $application->isBootstrap,
                settings: $resolvedSettings,
                dependencies: [],
            );

            $resolvedApplications[] = $resolved;
            $applicationsByPublicId[$application->workspaceApplicationPublicId] = $resolved;
        }

        return new WorkspaceRuntimeManifestResult(
            manifest: ['applications' => $manifestApplications],
            applications: $resolvedApplications,
            applicationsByPublicId: $applicationsByPublicId,
        );
    }
}
