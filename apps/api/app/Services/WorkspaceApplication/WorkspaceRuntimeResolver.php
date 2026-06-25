<?php

namespace App\Services\WorkspaceApplication;

use App\Exceptions\WorkspaceApplication\WorkspaceApplicationNotFoundException;
use App\Models\WorkspaceApplicationSetting;
use App\Services\WorkspaceApplication\Data\ResolvedWorkspaceApplication;
use App\Services\WorkspaceApplication\Data\RuntimeMembershipSnapshot;
use App\Services\WorkspaceApplication\Data\RuntimeOrganizationSnapshot;
use App\Services\WorkspaceApplication\Data\RuntimeWorkspaceSnapshot;
use App\Services\WorkspaceApplication\Data\WorkspaceApplicationRuntimeInput;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use App\Services\Runtime\Data\RuntimeManifest;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeSummary;
use App\Services\WorkspaceApplication\Data\WorkspaceSettingRuntimeInput;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Collection;

class WorkspaceRuntimeResolver implements WorkspaceRuntimeProvider
{
    public const GENERATED_BY = 'WorkspaceRuntimeResolver';

    public const SCHEMA_VERSION = 1;

    /**
     * @return array{audit: bool, settings: bool, workspace: bool, notifications: bool, automation: bool}
     */
    private const CAPABILITIES = [
        'audit' => true,
        'settings' => true,
        'workspace' => true,
        'notifications' => false,
        'automation' => false,
    ];

    public function __construct(
        private readonly WorkspaceApplicationService $workspaceApplicationService,
        private readonly WorkspaceSettingsService $workspaceSettingsService,
        private readonly WorkspaceRuntimeManifestBuilder $manifestBuilder,
        private readonly WorkspaceRuntimeVersionCalculator $versionCalculator,
        private readonly \App\Services\Module\ModuleLifecycleManager $moduleLifecycleManager,
    ) {
    }

    public function resolve(TenantContext $context, ?string $activeWorkspaceApplicationPublicId = null): WorkspaceRuntimeContext
    {
        return $this->moduleLifecycleManager->runtimeResolved(
            $context,
            fn () => $this->buildRuntimeContext($context, $activeWorkspaceApplicationPublicId),
        )['runtime'];
    }

    private function buildRuntimeContext(
        TenantContext $context,
        ?string $activeWorkspaceApplicationPublicId,
    ): WorkspaceRuntimeContext {
        $manifest = $this->buildManifest($context);
        $runtimeVersion = $this->versionCalculator->calculate($manifest);
        $settingsVersion = $this->workspaceSettingsService->resolveSettingsVersion($context);
        $activeApplication = $this->resolveActiveApplication(
            $manifest->applicationsByPublicId,
            $activeWorkspaceApplicationPublicId,
        );

        return new WorkspaceRuntimeContext(
            organization: $this->organizationSnapshot($context),
            workspace: $this->workspaceSnapshot($context),
            membership: $this->membershipSnapshot($context),
            activeApplications: $manifest->applications,
            activeApplication: $activeApplication,
            runtimeVersion: $runtimeVersion,
            settingsVersion: $settingsVersion,
            runtimeMetadata: $this->runtimeMetadata(),
            capabilities: self::CAPABILITIES,
        );
    }

    public function resolveSummary(TenantContext $context): WorkspaceRuntimeSummary
    {
        $manifest = $this->buildManifest($context);

        return new WorkspaceRuntimeSummary(
            activeApplicationCount: count($manifest->applications),
            runtimeVersion: $this->versionCalculator->calculate($manifest),
            settingsVersion: $this->workspaceSettingsService->resolveSettingsVersion($context),
        );
    }

    public function buildManifest(TenantContext $context): RuntimeManifest
    {
        $workspaceApplications = $this->workspaceApplicationService->listActiveForRuntime($context);

        $applicationInputs = [];
        $workspaceApplicationIds = [];

        foreach ($workspaceApplications as $workspaceApplication) {
            $workspaceApplicationIds[] = $workspaceApplication->id;
            $applicationInputs[] = new WorkspaceApplicationRuntimeInput(
                applicationId: $workspaceApplication->application_id,
                workspaceApplicationPublicId: $workspaceApplication->public_id,
                organizationApplicationPublicId: $workspaceApplication->organizationApplication->public_id,
                applicationPublicId: $workspaceApplication->application->public_id,
                key: $workspaceApplication->application->key,
                name: $workspaceApplication->application->name,
                workspaceApplicationStatus: $workspaceApplication->status->value,
                organizationApplicationStatus: $workspaceApplication->organizationApplication->status->value,
                catalogApplicationStatus: $workspaceApplication->application->status->value,
                enabledVersion: $workspaceApplication->enabled_version,
                catalogVersion: $workspaceApplication->application->version,
                isBootstrap: $workspaceApplication->is_bootstrap,
                capabilities: is_array($workspaceApplication->application->capabilities)
                    ? array_values($workspaceApplication->application->capabilities)
                    : [],
                dependencies: is_array($workspaceApplication->application->dependencies)
                    ? array_values($workspaceApplication->application->dependencies)
                    : [],
            );
        }

        $settingsByPublicId = $this->loadSettingsByWorkspaceApplicationPublicId(
            $workspaceApplications,
            $workspaceApplicationIds,
        );

        return $this->manifestBuilder->build($applicationInputs, $settingsByPublicId);
    }

    /**
     * @param  Collection<int, \App\Models\WorkspaceApplication>  $workspaceApplications
     * @param  list<string>  $workspaceApplicationIds
     * @return array<string, list<WorkspaceSettingRuntimeInput>>
     */
    private function loadSettingsByWorkspaceApplicationPublicId(
        Collection $workspaceApplications,
        array $workspaceApplicationIds,
    ): array {
        if ($workspaceApplicationIds === []) {
            return [];
        }

        $publicIdByInternalId = $workspaceApplications
            ->pluck('public_id', 'id')
            ->all();

        $settings = WorkspaceApplicationSetting::query()
            ->whereIn('workspace_application_id', $workspaceApplicationIds)
            ->whereNull('deleted_at')
            ->orderBy('setting_key')
            ->get();

        $grouped = [];

        foreach ($settings as $setting) {
            $workspaceApplicationPublicId = $publicIdByInternalId[$setting->workspace_application_id] ?? null;

            if ($workspaceApplicationPublicId === null) {
                continue;
            }

            $grouped[$workspaceApplicationPublicId] ??= [];
            $grouped[$workspaceApplicationPublicId][] = new WorkspaceSettingRuntimeInput(
                settingKey: $setting->setting_key,
                version: $setting->version,
                type: $setting->setting_type->value,
                value: $setting->setting_value,
                isSensitive: $setting->is_sensitive,
            );
        }

        return $grouped;
    }

    /**
     * @param  array<string, ResolvedWorkspaceApplication>  $applicationsByPublicId
     */
    private function resolveActiveApplication(
        array $applicationsByPublicId,
        ?string $activeWorkspaceApplicationPublicId,
    ): ?ResolvedWorkspaceApplication {
        if ($activeWorkspaceApplicationPublicId === null) {
            return null;
        }

        $activeApplication = $applicationsByPublicId[$activeWorkspaceApplicationPublicId] ?? null;

        if ($activeApplication === null) {
            throw new WorkspaceApplicationNotFoundException;
        }

        return $activeApplication;
    }

    private function organizationSnapshot(TenantContext $context): RuntimeOrganizationSnapshot
    {
        return new RuntimeOrganizationSnapshot(
            publicId: $context->organizationPublicId,
            name: $context->organization->name,
            slug: $context->organization->slug,
            status: $context->organization->status->value,
        );
    }

    private function workspaceSnapshot(TenantContext $context): RuntimeWorkspaceSnapshot
    {
        return new RuntimeWorkspaceSnapshot(
            publicId: $context->workspacePublicId,
            name: $context->workspace->name,
            slug: $context->workspace->slug,
            isDefault: $context->workspace->is_default,
            status: $context->workspace->status->value,
        );
    }

    private function membershipSnapshot(TenantContext $context): RuntimeMembershipSnapshot
    {
        return new RuntimeMembershipSnapshot(
            publicId: $context->membershipPublicId,
            status: $context->membership->status->value,
        );
    }

    /**
     * @return array{generated_at: string, generated_by: string, schema_version: int}
     */
    private function runtimeMetadata(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'generated_by' => self::GENERATED_BY,
            'schema_version' => self::SCHEMA_VERSION,
        ];
    }
}
