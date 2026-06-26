<?php

namespace App\Modules\Sdk\Workflow\Human\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Human\Data\ApprovalDecision;
use App\Modules\Sdk\Workflow\Human\Data\ApprovalReference;

interface ApprovalPort
{
    /**
     * @return list<ApprovalReference>
     */
    public function list(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array;

    public function get(EnterpriseScope $scope, string $publicId): ApprovalReference;

    public function approve(
        EnterpriseScope $scope,
        string $publicId,
        ?string $comment = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): ApprovalDecision;

    public function reject(
        EnterpriseScope $scope,
        string $publicId,
        ?string $comment = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): ApprovalDecision;
}
