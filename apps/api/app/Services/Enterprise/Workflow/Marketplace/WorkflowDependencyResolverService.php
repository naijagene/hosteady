<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Contracts\WorkflowDependencyResolver;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowCompatibilityReport;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowCompatibilityStatus;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class WorkflowDependencyResolverService implements WorkflowDependencyResolver
{
    public function __construct(
        private readonly WorkflowCompatibilityService $compatibilityService,
    ) {
    }

    public function resolve(
        EnterpriseScope $scope,
        WorkflowPackageManifest $manifest,
    ): WorkflowCompatibilityReport {
        return $this->compatibilityService->assessManifest($scope, $manifest);
    }
}
