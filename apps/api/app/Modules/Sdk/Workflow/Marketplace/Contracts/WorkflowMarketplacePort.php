<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowCompatibilityReport;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallRequest;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageReference;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageStatistics;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageVersion;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowRollbackResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowUpgradeResult;

interface WorkflowMarketplacePort
{
    /**
     * @return list<WorkflowPackageReference>
     */
    public function listPackages(EnterpriseScope $scope): array;

    public function showPackage(EnterpriseScope $scope, string $packagePublicId): WorkflowPackage;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createPackage(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackage;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function publishVersion(
        EnterpriseScope $scope,
        string $packagePublicId,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackageVersion;

    /**
     * @return array<string, mixed>
     */
    public function exportPackage(EnterpriseScope $scope, string $packagePublicId): array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function importPackage(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackage;

    public function installPackage(
        EnterpriseScope $scope,
        WorkflowInstallRequest $request,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstallResult;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upgradeInstall(
        EnterpriseScope $scope,
        string $installPublicId,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowUpgradeResult;

    public function rollbackInstall(
        EnterpriseScope $scope,
        string $installPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowRollbackResult;

    public function uninstallPackage(
        EnterpriseScope $scope,
        string $installPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstallResult;

    /**
     * @return list<WorkflowInstallResult>
     */
    public function listInstalled(EnterpriseScope $scope): array;

    /**
     * @return list<WorkflowPackageReference>
     */
    public function listUpdates(EnterpriseScope $scope): array;

    public function checkCompatibility(EnterpriseScope $scope, string $packagePublicId): WorkflowCompatibilityReport;

    public function statistics(EnterpriseScope $scope): WorkflowPackageStatistics;
}
