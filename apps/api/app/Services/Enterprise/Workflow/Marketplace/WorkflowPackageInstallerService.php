<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Models\WorkflowPackage;
use App\Models\WorkflowPackageHistory;
use App\Models\WorkflowPackageInstall;
use App\Models\WorkflowPackageVersion;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Designer\Enums\WorkflowImportFormat;
use App\Modules\Sdk\Workflow\Marketplace\Contracts\WorkflowPackageInstaller;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallRequest;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowRollbackResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowUpgradeResult;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowCompatibilityStatus;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowInstallStatus;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowInstallationException;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowPackageNotFoundException;
use App\Services\Enterprise\Workflow\Designer\WorkflowImportExportService;
use Illuminate\Support\Facades\DB;

class WorkflowPackageInstallerService implements WorkflowPackageInstaller
{
    public function __construct(
        private readonly WorkflowCompatibilityService $compatibilityService,
        private readonly WorkflowImportExportService $importExportService,
        private readonly WorkflowMarketplaceAuditRecorder $auditRecorder,
        private readonly WorkflowMarketplaceSearchIndexer $searchIndexer,
    ) {
    }

    public function install(
        EnterpriseScope $scope,
        WorkflowInstallRequest $request,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstallResult {
        $package = $this->findPackage($scope, $request->packagePublicId);
        $version = $this->resolveVersion($package, $request->versionPublicId, $request->targetVersion);
        $manifest = WorkflowPackageManifest::fromArray($version->manifest_json);

        $compatibility = $this->compatibilityService->assessManifest($scope, $manifest, $package->public_id);

        if ($compatibility->status === WorkflowCompatibilityStatus::Unsupported->value) {
            throw new WorkflowInstallationException(implode(' ', $compatibility->issues));
        }

        return DB::transaction(function () use (
            $scope,
            $package,
            $version,
            $manifest,
            $compatibility,
            $userId,
            $membershipId,
        ) {
            $importPayload = $this->buildImportPayload($manifest, $version);
            $imported = $this->importExportService->import($scope, $importPayload, $userId, $membershipId);

            $organizationId = \App\Models\Organization::query()
                ->where('public_id', $scope->organizationPublicId)
                ->value('id');

            $workspaceId = null;
            if ($scope->workspacePublicId !== null) {
                $workspaceId = \App\Models\Workspace::query()
                    ->where('public_id', $scope->workspacePublicId)
                    ->where('organization_id', $organizationId)
                    ->value('id');
            }

            $definition = \App\Models\WorkflowDefinition::query()
                ->where('public_id', $imported->workflowDefinitionPublicId)
                ->firstOrFail();

            $install = WorkflowPackageInstall::query()->create([
                'organization_id' => $organizationId,
                'workspace_id' => $workspaceId,
                'workflow_package_id' => $package->id,
                'workflow_package_version_id' => $version->id,
                'installed_workflow_definition_id' => $definition->id,
                'installed_version' => $version->version,
                'status' => WorkflowInstallStatus::Installed,
                'installed_at' => now(),
                'installed_by_user_id' => $userId,
                'installed_by_membership_id' => $membershipId,
                'metadata' => ['package_key' => $package->package_key],
            ]);

            $this->recordHistory($install, 'installed', null, [
                'installed_version' => $version->version,
                'workflow_definition_public_id' => $definition->public_id,
            ], $userId, $membershipId);

            $this->auditRecorder->recordInstalled($install->fresh(['workflowPackage', 'installedWorkflowDefinition']));
            $this->searchIndexer->indexInstallBestEffort($install->fresh(['workflowPackage', 'installedWorkflowDefinition']));

            return new WorkflowInstallResult(
                installPublicId: $install->public_id,
                packagePublicId: $package->public_id,
                packageVersionPublicId: $version->public_id,
                installedVersion: $version->version,
                status: WorkflowInstallStatus::Installed->value,
                installedWorkflowDefinitionPublicId: $definition->public_id,
                warnings: $compatibility->warnings,
            );
        });
    }

    public function upgrade(
        EnterpriseScope $scope,
        string $installPublicId,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowUpgradeResult {
        $install = $this->findInstall($scope, $installPublicId);
        $targetVersionPublicId = (string) ($payload['version_public_id'] ?? '');
        $targetVersion = isset($payload['target_version']) ? (string) $payload['target_version'] : null;
        $version = $this->resolveVersion($install->workflowPackage, $targetVersionPublicId ?: null, $targetVersion);
        $manifest = WorkflowPackageManifest::fromArray($version->manifest_json);

        $compatibility = $this->compatibilityService->assessManifest(
            $scope,
            $manifest,
            $install->workflowPackage->public_id,
        );

        if ($compatibility->status === WorkflowCompatibilityStatus::Unsupported->value) {
            throw new WorkflowInstallationException(implode(' ', $compatibility->issues));
        }

        $previousVersion = $install->installed_version;
        $beforeState = [
            'installed_version' => $install->installed_version,
            'workflow_definition_public_id' => $install->installedWorkflowDefinition?->public_id,
            'package_version_public_id' => $install->workflowPackageVersion?->public_id,
        ];

        return DB::transaction(function () use (
            $scope,
            $install,
            $version,
            $manifest,
            $compatibility,
            $previousVersion,
            $beforeState,
            $userId,
            $membershipId,
        ) {
            $importPayload = $this->buildImportPayload($manifest, $version);
            $imported = $this->importExportService->import($scope, $importPayload, $userId, $membershipId);
            $definition = \App\Models\WorkflowDefinition::query()
                ->where('public_id', $imported->workflowDefinitionPublicId)
                ->firstOrFail();

            $install->update([
                'workflow_package_version_id' => $version->id,
                'installed_workflow_definition_id' => $definition->id,
                'installed_version' => $version->version,
                'status' => WorkflowInstallStatus::Installed,
                'upgraded_at' => now(),
            ]);

            $afterState = [
                'installed_version' => $version->version,
                'workflow_definition_public_id' => $definition->public_id,
                'package_version_public_id' => $version->public_id,
            ];

            $this->recordHistory($install, 'upgraded', $beforeState, $afterState, $userId, $membershipId);
            $this->auditRecorder->recordUpgraded($install->fresh(['workflowPackage', 'installedWorkflowDefinition']));
            $this->searchIndexer->indexInstallBestEffort($install->fresh(['workflowPackage', 'installedWorkflowDefinition']));

            return new WorkflowUpgradeResult(
                installPublicId: $install->public_id,
                previousVersion: $previousVersion,
                installedVersion: $version->version,
                status: WorkflowInstallStatus::Installed->value,
                installedWorkflowDefinitionPublicId: $definition->public_id,
                warnings: $compatibility->warnings,
            );
        });
    }

    public function rollback(
        EnterpriseScope $scope,
        string $installPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowRollbackResult {
        $install = $this->findInstall($scope, $installPublicId);
        $history = WorkflowPackageHistory::query()
            ->where('workflow_package_install_id', $install->id)
            ->where('action', 'upgraded')
            ->orderByDesc('created_at')
            ->first();

        if ($history === null || ! is_array($history->before_state)) {
            return new WorkflowRollbackResult(
                installPublicId: $install->public_id,
                restoredVersion: $install->installed_version,
                status: WorkflowInstallStatus::Installed->value,
                installedWorkflowDefinitionPublicId: $install->installedWorkflowDefinition?->public_id,
                warnings: ['No upgrade history available for rollback. Current install preserved.'],
            );
        }

        $before = $history->before_state;
        $versionPublicId = (string) ($before['package_version_public_id'] ?? '');

        if ($versionPublicId === '') {
            return new WorkflowRollbackResult(
                installPublicId: $install->public_id,
                restoredVersion: $install->installed_version,
                status: WorkflowInstallStatus::Installed->value,
                installedWorkflowDefinitionPublicId: $install->installedWorkflowDefinition?->public_id,
                warnings: ['Rollback state is incomplete. Current install preserved.'],
            );
        }

        $version = WorkflowPackageVersion::query()->where('public_id', $versionPublicId)->first();

        if ($version === null) {
            return new WorkflowRollbackResult(
                installPublicId: $install->public_id,
                restoredVersion: $install->installed_version,
                status: WorkflowInstallStatus::Installed->value,
                installedWorkflowDefinitionPublicId: $install->installedWorkflowDefinition?->public_id,
                warnings: ['Previous package version no longer exists. Current install preserved.'],
            );
        }

        $manifest = WorkflowPackageManifest::fromArray($version->manifest_json);

        return DB::transaction(function () use ($scope, $install, $version, $manifest, $before, $userId, $membershipId) {
            $importPayload = $this->buildImportPayload($manifest, $version);
            $imported = $this->importExportService->import($scope, $importPayload, $userId, $membershipId);
            $definition = \App\Models\WorkflowDefinition::query()
                ->where('public_id', $imported->workflowDefinitionPublicId)
                ->firstOrFail();

            $install->update([
                'workflow_package_version_id' => $version->id,
                'installed_workflow_definition_id' => $definition->id,
                'installed_version' => $version->version,
                'status' => WorkflowInstallStatus::RolledBack,
                'rolled_back_at' => now(),
            ]);

            $this->recordHistory($install, 'rollback', $before, [
                'installed_version' => $version->version,
                'workflow_definition_public_id' => $definition->public_id,
            ], $userId, $membershipId);

            $this->auditRecorder->recordRollback($install->fresh(['workflowPackage', 'installedWorkflowDefinition']));

            return new WorkflowRollbackResult(
                installPublicId: $install->public_id,
                restoredVersion: $version->version,
                status: WorkflowInstallStatus::RolledBack->value,
                installedWorkflowDefinitionPublicId: $definition->public_id,
            );
        });
    }

    public function uninstall(
        EnterpriseScope $scope,
        string $installPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstallResult {
        $install = $this->findInstall($scope, $installPublicId);

        $install->update([
            'status' => WorkflowInstallStatus::Uninstalled,
            'uninstalled_at' => now(),
        ]);

        $this->recordHistory($install, 'uninstalled', [
            'installed_version' => $install->installed_version,
        ], null, $userId, $membershipId);

        $this->auditRecorder->recordUninstalled($install->fresh(['workflowPackage']));

        return new WorkflowInstallResult(
            installPublicId: $install->public_id,
            packagePublicId: $install->workflowPackage->public_id,
            packageVersionPublicId: $install->workflowPackageVersion->public_id,
            installedVersion: $install->installed_version,
            status: WorkflowInstallStatus::Uninstalled->value,
            installedWorkflowDefinitionPublicId: $install->installedWorkflowDefinition?->public_id,
        );
    }

    /**
     * @param  array<string, mixed>|null  $beforeState
     * @param  array<string, mixed>|null  $afterState
     */
    private function recordHistory(
        WorkflowPackageInstall $install,
        string $action,
        ?array $beforeState,
        ?array $afterState,
        ?string $userId,
        ?string $membershipId,
    ): void {
        WorkflowPackageHistory::query()->create([
            'workflow_package_id' => $install->workflow_package_id,
            'workflow_package_install_id' => $install->id,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'created_by_user_id' => $userId,
            'created_by_membership_id' => $membershipId,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildImportPayload(WorkflowPackageManifest $manifest, WorkflowPackageVersion $version): array
    {
        $workflow = $manifest->workflow;
        $installStatus = $manifest->metadata['install_status'] ?? 'draft';

        if (! isset($workflow['definition']) && isset($workflow['nodes'])) {
            $workflow = [
                'workflow_key' => $manifest->key,
                'name' => $manifest->name,
                'description' => $manifest->description,
                'module_key' => $manifest->moduleKey,
                'metadata' => array_merge($manifest->metadata, ['install_status' => $installStatus]),
                'definition' => [
                    'nodes' => $workflow['nodes'] ?? [],
                    'transitions' => $workflow['transitions'] ?? [],
                    'triggers' => $workflow['triggers'] ?? [],
                ],
                'variables' => $manifest->variables,
            ];
        }

        return [
            'format' => WorkflowImportFormat::HeosJson->value,
            'workflow' => $workflow,
            'canvas' => $manifest->canvas !== [] ? $manifest->canvas : null,
        ];
    }

    private function resolveVersion(
        WorkflowPackage $package,
        ?string $versionPublicId,
        ?string $targetVersion,
    ): WorkflowPackageVersion {
        if ($versionPublicId !== null && $versionPublicId !== '') {
            $version = WorkflowPackageVersion::query()
                ->where('public_id', $versionPublicId)
                ->where('workflow_package_id', $package->id)
                ->first();

            if ($version !== null) {
                return $version;
            }
        }

        if ($targetVersion !== null && $targetVersion !== '') {
            $version = $package->versions()->where('version', $targetVersion)->first();

            if ($version !== null) {
                return $version;
            }
        }

        $version = $package->versions()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->first()
            ?? $package->versions()->orderByDesc('created_at')->first();

        if ($version === null) {
            throw new WorkflowInstallationException('No package version is available for installation.');
        }

        return $version;
    }

    private function findPackage(EnterpriseScope $scope, string $packagePublicId): WorkflowPackage
    {
        $organizationId = \App\Models\Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');

        $package = WorkflowPackage::query()
            ->with(['versions'])
            ->where('public_id', $packagePublicId)
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
            })
            ->first();

        if ($package === null) {
            throw new WorkflowPackageNotFoundException(sprintf('Workflow package [%s] was not found.', $packagePublicId));
        }

        return $package;
    }

    private function findInstall(EnterpriseScope $scope, string $installPublicId): WorkflowPackageInstall
    {
        $organizationId = \App\Models\Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');

        $workspaceId = null;
        if ($scope->workspacePublicId !== null) {
            $workspaceId = \App\Models\Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');
        }

        $install = WorkflowPackageInstall::query()
            ->with(['workflowPackage', 'workflowPackageVersion', 'installedWorkflowDefinition'])
            ->where('public_id', $installPublicId)
            ->where('organization_id', $organizationId)
            ->when($workspaceId !== null, fn ($q) => $q->where('workspace_id', $workspaceId))
            ->first();

        if ($install === null) {
            throw new WorkflowPackageNotFoundException(sprintf('Workflow package install [%s] was not found.', $installPublicId));
        }

        return $install;
    }
}
