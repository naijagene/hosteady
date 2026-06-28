<?php

namespace App\Services\WorkspaceApplication;

use App\Exceptions\WorkspaceApplication\WorkspaceApplicationNotFoundException;
use App\Models\WorkspaceApplicationSetting;
use App\Modules\Sdk\Runtime\RuntimePipelineReport;
use App\Services\Module\RuntimeExtensionService;
use App\Services\Module\ModuleLifecycleManager;
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
     * @return array{audit: bool, settings: bool, workspace: bool, notifications: bool, events: bool, reference_data: bool, storage: bool, media: bool, jobs: bool, scheduler: bool, search: bool, indexing: bool, workflow: bool, human_tasks: bool, approvals: bool, approval: bool, automation: bool, workflow_designer: bool, workflow_marketplace: bool, business_modules: bool, entities: bool, forms: bool, tables: bool, dashboards: bool, reports: bool}
     */
    private function platformCapabilities(): array
    {
        return [
            'audit' => true,
            'settings' => true,
            'workspace' => true,
            'notifications' => (bool) config('heos.enterprise.notifications.enabled', true),
            'events' => (bool) config('heos.enterprise.event_bus.enabled', true),
            'reference_data' => (bool) config('heos.enterprise.reference_data.enabled', true),
            'storage' => (bool) config('heos.enterprise.files.enabled', true),
            'media' => (bool) config('heos.enterprise.files.enabled', true),
            'jobs' => (bool) config('heos.enterprise.jobs.enabled', true),
            'scheduler' => (bool) config('heos.enterprise.scheduler.enabled', true),
            'search' => (bool) config('heos.enterprise.search.enabled', true),
            'indexing' => (bool) config('heos.enterprise.search.enabled', true),
            'workflow' => (bool) config('heos.enterprise.workflow.enabled', true),
            'human_tasks' => (bool) config('heos.enterprise.human_tasks.enabled', true),
            'approvals' => (bool) config('heos.enterprise.approvals.enabled', true),
            'approval' => (bool) config('heos.enterprise.approvals.enabled', true),
            'automation' => (bool) config('heos.enterprise.automation.enabled', true),
            'workflow_designer' => (bool) config('heos.enterprise.workflow_designer.enabled', true),
            'workflow_marketplace' => (bool) config('heos.enterprise.workflow_marketplace.enabled', true),
            'business_modules' => (bool) config('heos.enterprise.business_modules.enabled', true),
            'entities' => (bool) config('heos.enterprise.entities.enabled', true),
            'forms' => (bool) config('heos.enterprise.forms.enabled', true),
            'tables' => (bool) config('heos.enterprise.tables.enabled', true),
            'dashboards' => (bool) config('heos.enterprise.dashboards.enabled', true),
            'reports' => (bool) config('heos.enterprise.reports.enabled', true),
        ];
    }

    public function __construct(
        private readonly WorkspaceApplicationService $workspaceApplicationService,
        private readonly WorkspaceSettingsService $workspaceSettingsService,
        private readonly WorkspaceRuntimeManifestBuilder $manifestBuilder,
        private readonly WorkspaceRuntimeVersionCalculator $versionCalculator,
        private readonly ModuleLifecycleManager $moduleLifecycleManager,
        private readonly RuntimeExtensionService $runtimeExtensionService,
        private readonly \App\Modules\Sdk\Runtime\RuntimeExtensionManager $runtimeExtensionManager,
        private readonly \App\Services\Enterprise\FileMedia\EnterpriseStorageHealthService $storageHealthService,
        private readonly \App\Services\Enterprise\Jobs\PlatformJobHealthService $jobHealthService,
        private readonly \App\Services\Enterprise\Scheduler\SchedulerHealthService $schedulerHealthService,
        private readonly \App\Services\Enterprise\Search\SearchHealthService $searchHealthService,
        private readonly \App\Services\Enterprise\Workflow\WorkflowHealthService $workflowHealthService,
        private readonly \App\Services\Module\Development\BusinessModuleHealthService $businessModuleHealthService,
        private readonly \App\Services\Entity\EnterpriseEntityHealthService $entityHealthService,
        private readonly \App\Services\Form\DynamicFormHealthService $formHealthService,
        private readonly \App\Services\Table\DynamicTableHealthService $tableHealthService,
        private readonly \App\Services\Dashboard\DynamicDashboardHealthService $dashboardHealthService,
        private readonly \App\Services\Report\DynamicReportHealthService $reportHealthService,
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
        $extensionReport = $this->resolveExtensions($context, $manifest);
        $mergedExtensions = $extensionReport->contributions->merge();
        $runtimeVersion = $this->versionCalculator->calculate(
            $manifest,
            $extensionReport->contributions->fingerprint(),
        );
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
            runtimeMetadata: $this->mergeRuntimeMetadata(
                $this->runtimeMetadata($context),
                $mergedExtensions['runtime_metadata'],
            ),
            capabilities: $this->runtimeExtensionManager->mergeCapabilities(
                $this->platformCapabilities(),
                $extensionReport->contributions,
            ),
            navigation: $mergedExtensions['navigation'],
            featureFlags: $mergedExtensions['feature_flags'],
            moduleDiagnostics: $mergedExtensions['diagnostics'],
            settingsMetadata: $mergedExtensions['settings_metadata'],
        );
    }

    public function resolveSummary(TenantContext $context): WorkspaceRuntimeSummary
    {
        $manifest = $this->buildManifest($context);
        $extensionReport = $this->resolveExtensions($context, $manifest);

        return new WorkspaceRuntimeSummary(
            activeApplicationCount: count($manifest->applications),
            runtimeVersion: $this->versionCalculator->calculate(
                $manifest,
                $extensionReport->contributions->fingerprint(),
            ),
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

    private function resolveExtensions(TenantContext $context, RuntimeManifest $manifest): RuntimePipelineReport
    {
        $activeModuleKeys = array_map(
            fn (ResolvedWorkspaceApplication $application) => $application->key,
            $manifest->applications,
        );

        return $this->runtimeExtensionService->resolveForTenant($context, $activeModuleKeys);
    }

    /**
     * @param  array<string, mixed>  $platformMetadata
     * @param  array<string, mixed>  $contributionMetadata
     * @return array<string, mixed>
     */
    private function mergeRuntimeMetadata(array $platformMetadata, array $contributionMetadata): array
    {
        foreach ($contributionMetadata as $key => $value) {
            if (is_array($value) && isset($platformMetadata[$key]) && is_array($platformMetadata[$key]) && ! array_is_list($value)) {
                $platformMetadata[$key] = $this->mergeRuntimeMetadata($platformMetadata[$key], $value);
            } else {
                $platformMetadata[$key] = $value;
            }
        }

        return $platformMetadata;
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
     * @return array{generated_at: string, generated_by: string, schema_version: int, enterprise?: array<string, mixed>}
     */
    private function runtimeMetadata(TenantContext $context): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'generated_by' => self::GENERATED_BY,
            'schema_version' => self::SCHEMA_VERSION,
            'enterprise' => [
                'storage' => $this->storageHealthService->runtimeContribution($context),
                'jobs' => $this->jobHealthService->runtimeContribution($context),
                'scheduler' => $this->schedulerHealthService->runtimeContribution($context),
                'search' => $this->searchHealthService->runtimeContribution($context),
                'workflow' => $this->workflowHealthService->runtimeContribution($context),
                'business_modules' => $this->businessModuleHealthService->runtimeContribution($context),
                'entities' => $this->entityHealthService->runtimeContribution($context),
                'forms' => $this->formHealthService->runtimeContribution($context),
                'tables' => $this->tableHealthService->runtimeContribution($context),
                'dashboards' => $this->dashboardHealthService->runtimeContribution($context),
                'reports' => $this->reportHealthService->runtimeContribution($context),
            ],
        ];
    }
}
