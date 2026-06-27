<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;

interface WorkflowPackageExporter
{
    /**
     * @return array<string, mixed>
     */
    public function export(EnterpriseScope $scope, string $packagePublicId): array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function import(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackage;

    public function buildManifestFromPayload(array $payload): WorkflowPackageManifest;
}
