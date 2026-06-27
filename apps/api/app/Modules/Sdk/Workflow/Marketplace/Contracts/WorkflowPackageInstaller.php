<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallRequest;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowRollbackResult;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowUpgradeResult;

interface WorkflowPackageInstaller
{
    public function install(
        EnterpriseScope $scope,
        WorkflowInstallRequest $request,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstallResult;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upgrade(
        EnterpriseScope $scope,
        string $installPublicId,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowUpgradeResult;

    public function rollback(
        EnterpriseScope $scope,
        string $installPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowRollbackResult;

    public function uninstall(
        EnterpriseScope $scope,
        string $installPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstallResult;
}
