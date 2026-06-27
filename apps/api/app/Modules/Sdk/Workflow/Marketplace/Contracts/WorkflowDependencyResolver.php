<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowCompatibilityReport;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;

interface WorkflowDependencyResolver
{
    public function resolve(
        EnterpriseScope $scope,
        WorkflowPackageManifest $manifest,
    ): WorkflowCompatibilityReport;
}
