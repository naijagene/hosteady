<?php

namespace App\Services\Module\Development;

use App\Models\BusinessModule;
use App\Models\BusinessModuleHistory;
use App\Models\BusinessModuleInstallation;
use App\Models\Organization;
use App\Models\Workspace;
use App\Modules\Sdk\Development\BusinessModuleBase;
use App\Modules\Sdk\Development\Contracts\BusinessModule as BusinessModuleContract;
use App\Modules\Sdk\Development\Contracts\BusinessModuleInstaller;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallResult;
use App\Modules\Sdk\Development\Enums\BusinessModuleInstallStatus;
use App\Modules\Sdk\Development\Exceptions\BusinessModuleInstallException;
use App\Modules\Sdk\Development\Exceptions\BusinessModuleNotFoundException;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessModuleInstallerService implements BusinessModuleInstaller
{
    public function __construct(
        private readonly BusinessModuleRegistryService $registryService,
        private readonly BusinessModuleValidatorService $validator,
        private readonly BusinessModuleAuditRecorder $auditRecorder,
        private readonly BusinessModuleSearchIndexer $searchIndexer,
        private readonly \App\Services\Entity\EnterpriseEntityRegistryService $entityRegistryService,
        private readonly \App\Services\Form\DynamicFormRegistryService $formRegistryService,
        private readonly \App\Services\Table\DynamicTableRegistryService $tableRegistryService,
        private readonly \App\Services\Dashboard\DynamicDashboardRegistryService $dashboardRegistryService,
        private readonly \App\Services\Report\DynamicReportRegistryService $reportRegistryService,
        private readonly \App\Services\Ui\UiPageRegistryService $uiPageRegistryService,
        private readonly \App\Services\Ui\UiLayoutService $uiLayoutService,
        private readonly \App\Services\Ui\UiComponentService $uiComponentService,
    ) {
    }

    public function install(
        EnterpriseScope $scope,
        BusinessModuleInstallRequest $request,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        return $this->installFromManifest($scope, $request, $userId, $membershipId);
    }

    public function installModule(
        EnterpriseScope $scope,
        BusinessModuleContract|BusinessModuleBase $module,
        BusinessModuleInstallRequest $request,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        $reference = $this->registryService->register($module, $userId, $membershipId);

        return $this->installFromManifest(
            $scope,
            new BusinessModuleInstallRequest(
                modulePublicId: $reference->publicId,
                settings: $request->settings,
                metadata: $request->metadata,
            ),
            $userId,
            $membershipId,
        );
    }

    private function installFromManifest(
        EnterpriseScope $scope,
        BusinessModuleInstallRequest $request,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        $module = BusinessModule::query()->where('public_id', $request->modulePublicId)->first();

        if ($module === null) {
            throw new BusinessModuleNotFoundException(sprintf('Business module [%s] was not found.', $request->modulePublicId));
        }

        $manifest = BusinessModuleMapper::toManifest($module);
        $this->validator->assertValid($manifest);

        $organization = Organization::query()->where('public_id', $scope->organizationPublicId)->firstOrFail();
        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        return DB::transaction(function () use ($module, $manifest, $organization, $workspaceId, $request, $userId, $membershipId) {
            $this->seedPermissions($manifest);
            $this->registerManifestEntities($manifest);
            $this->registerManifestForms($manifest);
            $this->registerManifestTables($manifest);
            $this->registerManifestDashboards($manifest);
            $this->registerManifestReports($manifest);
            $this->registerManifestUi($manifest, $organization->id, $workspaceId);

            $installation = BusinessModuleInstallation::query()->create([
                'organization_id' => $organization->id,
                'workspace_id' => $workspaceId,
                'business_module_id' => $module->id,
                'installed_version' => $module->version,
                'status' => BusinessModuleInstallStatus::Installed,
                'settings' => $request->settings,
                'installed_at' => now(),
                'installed_by_user_id' => $userId,
                'installed_by_membership_id' => $membershipId,
                'metadata' => $request->metadata,
            ]);

            $module->update([
                'status' => \App\Modules\Sdk\Development\Enums\BusinessModuleStatus::Registered,
                'installed_at' => now(),
            ]);

            $this->recordHistory($module, $installation, 'installed', null, [
                'installed_version' => $module->version,
            ], $userId, $membershipId);

            $this->auditRecorder->recordInstalled($installation->fresh('businessModule'));
            $this->searchIndexer->indexInstallationBestEffort($installation->fresh('businessModule'));

            return $this->toResult($installation->fresh('businessModule'));
        });
    }

    public function enable(
        EnterpriseScope $scope,
        string $installationPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        $installation = $this->findInstallation($scope, $installationPublicId);

        if ($installation->status === BusinessModuleInstallStatus::Uninstalled) {
            throw new BusinessModuleInstallException('Cannot enable an uninstalled business module.');
        }

        $installation->update([
            'status' => BusinessModuleInstallStatus::Enabled,
            'enabled_at' => now(),
            'disabled_at' => null,
        ]);

        $installation->businessModule?->update([
            'status' => \App\Modules\Sdk\Development\Enums\BusinessModuleStatus::Enabled,
            'enabled_at' => now(),
            'disabled_at' => null,
        ]);

        $this->recordHistory($installation->businessModule, $installation, 'enabled', null, [
            'status' => BusinessModuleInstallStatus::Enabled->value,
        ], $userId, $membershipId);

        $this->auditRecorder->recordEnabled($installation->fresh('businessModule'));

        return $this->toResult($installation->fresh('businessModule'));
    }

    public function disable(
        EnterpriseScope $scope,
        string $installationPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        $installation = $this->findInstallation($scope, $installationPublicId);

        $installation->update([
            'status' => BusinessModuleInstallStatus::Disabled,
            'disabled_at' => now(),
        ]);

        $installation->businessModule?->update([
            'status' => \App\Modules\Sdk\Development\Enums\BusinessModuleStatus::Disabled,
            'disabled_at' => now(),
        ]);

        $this->recordHistory($installation->businessModule, $installation, 'disabled', null, [
            'status' => BusinessModuleInstallStatus::Disabled->value,
        ], $userId, $membershipId);

        $this->auditRecorder->recordDisabled($installation->fresh('businessModule'));

        return $this->toResult($installation->fresh('businessModule'));
    }

    public function uninstall(
        EnterpriseScope $scope,
        string $installationPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        $installation = $this->findInstallation($scope, $installationPublicId);

        $beforeStatus = $installation->status->value;

        $installation->update([
            'status' => BusinessModuleInstallStatus::Uninstalled,
            'disabled_at' => now(),
        ]);

        $this->recordHistory($installation->businessModule, $installation, 'uninstalled', [
            'status' => $beforeStatus,
        ], null, $userId, $membershipId);

        $this->auditRecorder->recordUninstalled($installation->fresh('businessModule'));

        return $this->toResult($installation->fresh('businessModule'));
    }

    /**
     * @return list<BusinessModuleInstallResult>
     */
    public function listInstalled(EnterpriseScope $scope): array
    {
        $organizationId = Organization::query()->where('public_id', $scope->organizationPublicId)->value('id');
        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');
        }

        return BusinessModuleInstallation::query()
            ->with('businessModule')
            ->where('organization_id', $organizationId)
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->whereIn('status', [
                BusinessModuleInstallStatus::Installed,
                BusinessModuleInstallStatus::Enabled,
                BusinessModuleInstallStatus::Disabled,
            ])
            ->orderByDesc('installed_at')
            ->get()
            ->map(fn (BusinessModuleInstallation $installation) => $this->toResult($installation))
            ->all();
    }

    private function toResult(BusinessModuleInstallation $installation): BusinessModuleInstallResult
    {
        return new BusinessModuleInstallResult(
            installationPublicId: $installation->public_id,
            modulePublicId: $installation->businessModule->public_id,
            moduleKey: $installation->businessModule->module_key,
            installedVersion: $installation->installed_version,
            status: $installation->status->value,
            settings: $installation->settings ?? [],
        );
    }

    /**
     * @param  array<string, mixed>|null  $beforeState
     * @param  array<string, mixed>|null  $afterState
     */
    private function recordHistory(
        ?BusinessModule $module,
        BusinessModuleInstallation $installation,
        string $action,
        ?array $beforeState,
        ?array $afterState,
        ?string $userId,
        ?string $membershipId,
    ): void {
        BusinessModuleHistory::query()->create([
            'business_module_id' => $module?->id,
            'business_module_installation_id' => $installation->id,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'created_by_user_id' => $userId,
            'created_by_membership_id' => $membershipId,
            'created_at' => now(),
        ]);
    }

    private function seedPermissions(\App\Modules\Sdk\Development\Data\BusinessModuleManifest $manifest): void
    {
        foreach ($manifest->permissions as $permission) {
            $existing = \App\Models\Permission::query()->where('key', $permission->key)->first();

            if ($existing !== null) {
                $existing->update([
                    'name' => $permission->name,
                    'description' => $permission->description,
                    'domain' => $permission->domain ?? 'business',
                ]);

                continue;
            }

            \App\Models\Permission::query()->create([
                'id' => (string) Str::uuid7(),
                'public_id' => (string) Str::uuid7(),
                'key' => $permission->key,
                'name' => $permission->name,
                'description' => $permission->description,
                'domain' => $permission->domain ?? 'business',
            ]);
        }
    }

    private function registerManifestEntities(\App\Modules\Sdk\Development\Data\BusinessModuleManifest $manifest): void
    {
        if ($manifest->entities === []) {
            return;
        }

        if (! (bool) config('heos.enterprise.entities.enabled', true)) {
            return;
        }

        try {
            $this->entityRegistryService->registerFromManifestEntities(
                $manifest->entities,
                $manifest->moduleKey,
            );
        } catch (\Throwable) {
            // Entity registration must not block module installation.
        }
    }

    private function registerManifestForms(\App\Modules\Sdk\Development\Data\BusinessModuleManifest $manifest): void
    {
        if ($manifest->forms === []) {
            return;
        }

        if (! (bool) config('heos.enterprise.forms.enabled', true)) {
            return;
        }

        try {
            $this->formRegistryService->registerFromManifestForms(
                $manifest->forms,
                $manifest->moduleKey,
            );
        } catch (\Throwable) {
            // Form registration must not block module installation.
        }
    }

    private function registerManifestTables(\App\Modules\Sdk\Development\Data\BusinessModuleManifest $manifest): void
    {
        if ($manifest->tables === []) {
            return;
        }

        if (! (bool) config('heos.enterprise.tables.enabled', true)) {
            return;
        }

        try {
            $this->tableRegistryService->registerFromManifestTables(
                $manifest->tables,
                $manifest->moduleKey,
            );
        } catch (\Throwable) {
            // Table registration must not block module installation.
        }
    }

    private function registerManifestDashboards(\App\Modules\Sdk\Development\Data\BusinessModuleManifest $manifest): void
    {
        if ($manifest->dashboards === []) {
            return;
        }

        if (! (bool) config('heos.enterprise.dashboards.enabled', true)) {
            return;
        }

        try {
            $this->dashboardRegistryService->registerFromManifestDashboards(
                $manifest->dashboards,
                $manifest->moduleKey,
            );
        } catch (\Throwable) {
            // Dashboard registration must not block module installation.
        }
    }

    private function registerManifestReports(\App\Modules\Sdk\Development\Data\BusinessModuleManifest $manifest): void
    {
        if ($manifest->reports === []) {
            return;
        }

        if (! (bool) config('heos.enterprise.reports.enabled', true)) {
            return;
        }

        try {
            $this->reportRegistryService->registerFromManifestReports(
                $manifest->reports,
                $manifest->moduleKey,
            );
        } catch (\Throwable) {
            // Report registration must not block module installation.
        }
    }

    private function registerManifestUi(
        \App\Modules\Sdk\Development\Data\BusinessModuleManifest $manifest,
        string $organizationId,
        ?string $workspaceId,
    ): void {
        if ($manifest->uiPages === [] && $manifest->uiLayouts === [] && $manifest->uiComponents === []) {
            return;
        }

        if (! (bool) config('heos.enterprise.ui_metadata.enabled', true)) {
            return;
        }

        foreach ($manifest->uiPages as $page) {
            if (! is_array($page)) {
                continue;
            }

            try {
                $payload = array_merge($page, ['module_key' => $manifest->moduleKey]);
                $this->uiPageRegistryService->registerFromSource($organizationId, $workspaceId, null, $payload);
            } catch (\Throwable) {
                // UI page registration must not block module installation.
            }
        }

        foreach ($manifest->uiLayouts as $layout) {
            if (! is_array($layout)) {
                continue;
            }

            try {
                $payload = array_merge($layout, ['module_key' => $manifest->moduleKey]);
                $this->uiLayoutService->registerFromSource($organizationId, $workspaceId, null, $payload);
            } catch (\Throwable) {
                // UI layout registration must not block module installation.
            }
        }

        foreach ($manifest->uiComponents as $component) {
            if (! is_array($component)) {
                continue;
            }

            try {
                $payload = array_merge($component, ['module_key' => $manifest->moduleKey]);
                $this->uiComponentService->registerFromSource($organizationId, $workspaceId, null, $payload);
            } catch (\Throwable) {
                // UI component registration must not block module installation.
            }
        }
    }

    private function findInstallation(EnterpriseScope $scope, string $installationPublicId): BusinessModuleInstallation
    {
        $organizationId = Organization::query()->where('public_id', $scope->organizationPublicId)->value('id');
        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');
        }

        $installation = BusinessModuleInstallation::query()
            ->with('businessModule')
            ->where('public_id', $installationPublicId)
            ->where('organization_id', $organizationId)
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->first();

        if ($installation === null) {
            throw new BusinessModuleNotFoundException(sprintf('Business module installation [%s] was not found.', $installationPublicId));
        }

        return $installation;
    }
}
