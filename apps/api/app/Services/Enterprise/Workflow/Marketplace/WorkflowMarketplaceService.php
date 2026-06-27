<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Contracts\WorkflowMarketplacePort;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowCompatibilityReport;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallRequest;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageStatistics;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageVersion;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowRollbackResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowUpgradeResult;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WorkflowMarketplaceService
{
    public function __construct(
        private readonly WorkflowMarketplacePort $marketplacePort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    /**
     * @return list<\App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageReference>
     */
    public function listPackages(TenantContext $context): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertReadPermission($context);

        return $this->marketplacePort->listPackages($this->scope($context));
    }

    public function showPackage(TenantContext $context, string $packagePublicId): WorkflowPackage
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertReadPermission($context);

        return $this->marketplacePort->showPackage($this->scope($context), $packagePublicId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createPackage(TenantContext $context, array $payload): WorkflowPackage
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertManagePermission($context);

        return $this->marketplacePort->createPackage(
            $this->scope($context),
            $payload,
            $context->user->id,
            $context->membership->id,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function publishVersion(TenantContext $context, string $packagePublicId, array $payload): WorkflowPackageVersion
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertPublishPermission($context);

        return $this->marketplacePort->publishVersion(
            $this->scope($context),
            $packagePublicId,
            $payload,
            $context->user->id,
            $context->membership->id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function exportPackage(TenantContext $context, string $packagePublicId): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertExportPermission($context);

        return $this->marketplacePort->exportPackage($this->scope($context), $packagePublicId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function importPackage(TenantContext $context, array $payload): WorkflowPackage
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertManagePermission($context);

        return $this->marketplacePort->importPackage(
            $this->scope($context),
            $payload,
            $context->user->id,
            $context->membership->id,
        );
    }

    public function installPackage(TenantContext $context, WorkflowInstallRequest $request): WorkflowInstallResult
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertInstallPermission($context);

        return $this->marketplacePort->installPackage(
            $this->scope($context),
            $request,
            $context->user->id,
            $context->membership->id,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upgradeInstall(TenantContext $context, string $installPublicId, array $payload): WorkflowUpgradeResult
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertInstallPermission($context);

        return $this->marketplacePort->upgradeInstall(
            $this->scope($context),
            $installPublicId,
            $payload,
            $context->user->id,
            $context->membership->id,
        );
    }

    public function rollbackInstall(TenantContext $context, string $installPublicId): WorkflowRollbackResult
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertInstallPermission($context);

        return $this->marketplacePort->rollbackInstall(
            $this->scope($context),
            $installPublicId,
            $context->user->id,
            $context->membership->id,
        );
    }

    public function uninstallPackage(TenantContext $context, string $installPublicId): WorkflowInstallResult
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertInstallPermission($context);

        return $this->marketplacePort->uninstallPackage(
            $this->scope($context),
            $installPublicId,
            $context->user->id,
            $context->membership->id,
        );
    }

    /**
     * @return list<WorkflowInstallResult>
     */
    public function listInstalled(TenantContext $context): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertReadPermission($context);

        return $this->marketplacePort->listInstalled($this->scope($context));
    }

    /**
     * @return list<\App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageReference>
     */
    public function listUpdates(TenantContext $context): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertReadPermission($context);

        return $this->marketplacePort->listUpdates($this->scope($context));
    }

    public function checkCompatibility(TenantContext $context, string $packagePublicId): WorkflowCompatibilityReport
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertReadPermission($context);

        return $this->marketplacePort->checkCompatibility($this->scope($context), $packagePublicId);
    }

    public function statistics(TenantContext $context): WorkflowPackageStatistics
    {
        $this->runtimeBridge->requireCapability($context, 'workflow_marketplace');
        $this->assertReadPermission($context);

        return $this->marketplacePort->statistics($this->scope($context));
    }

    private function scope(TenantContext $context): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
        );
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'workflow.marketplace.read')) {
            throw new HttpException(403, 'You do not have permission to read workflow marketplace packages.');
        }
    }

    private function assertInstallPermission(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'workflow.marketplace.install')) {
            throw new HttpException(403, 'You do not have permission to install workflow marketplace packages.');
        }
    }

    private function assertPublishPermission(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'workflow.marketplace.publish')) {
            throw new HttpException(403, 'You do not have permission to publish workflow marketplace packages.');
        }
    }

    private function assertManagePermission(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'workflow.marketplace.manage')) {
            throw new HttpException(403, 'You do not have permission to manage workflow marketplace packages.');
        }
    }

    private function assertExportPermission(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'workflow.marketplace.export')) {
            throw new HttpException(403, 'You do not have permission to export workflow marketplace packages.');
        }
    }
}
