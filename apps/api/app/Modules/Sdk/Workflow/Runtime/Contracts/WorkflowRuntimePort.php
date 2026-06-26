<?php

namespace App\Modules\Sdk\Workflow\Runtime\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionResult;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionStatistics;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowInstanceReference;

interface WorkflowRuntimePort
{
    public function execute(
        EnterpriseScope $scope,
        string $definitionPublicId,
        WorkflowExecutionContext $context,
        ?array $inputPayload = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowExecutionResult;

    /**
     * @return list<WorkflowInstanceReference>
     */
    public function listInstances(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array;

    public function getInstance(EnterpriseScope $scope, string $publicId): WorkflowInstanceReference;

    public function cancel(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowInstanceReference;

    public function resume(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowExecutionResult;

    /**
     * @return array{steps: list<\App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionReference>, logs: list<array<string, mixed>>}
     */
    public function history(EnterpriseScope $scope, string $publicId): array;

    public function statistics(EnterpriseScope $scope): WorkflowExecutionStatistics;
}
