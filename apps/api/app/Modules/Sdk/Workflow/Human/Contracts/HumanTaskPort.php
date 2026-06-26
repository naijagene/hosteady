<?php

namespace App\Modules\Sdk\Workflow\Human\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Human\Data\HumanTaskReference;
use App\Modules\Sdk\Workflow\Human\Data\HumanTaskResult;
use App\Modules\Sdk\Workflow\Human\Data\TaskComment;
use App\Modules\Sdk\Workflow\Human\Data\TaskHistory;
use App\Modules\Sdk\Workflow\Human\Data\TaskStatistics;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;

interface HumanTaskPort
{
    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $variables
     */
    public function createFromWorkflowNode(
        EnterpriseScope $scope,
        string $workflowInstanceId,
        string $nodeType,
        array $node,
        WorkflowExecutionContext $context,
        array $variables,
        ?string $userId = null,
        ?string $membershipId = null,
    ): HumanTaskResult;

    /**
     * @return list<HumanTaskReference>
     */
    public function list(EnterpriseScope $scope, ?string $status = null, int $limit = 50): array;

    public function get(EnterpriseScope $scope, string $publicId): HumanTaskReference;

    public function open(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): HumanTaskReference;

    public function complete(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
        ?array $result = null,
    ): HumanTaskReference;

    public function cancel(
        EnterpriseScope $scope,
        string $publicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): HumanTaskReference;

    public function addComment(
        EnterpriseScope $scope,
        string $publicId,
        string $body,
        ?string $userId = null,
        ?string $membershipId = null,
    ): TaskComment;

    /**
     * @return list<TaskComment>
     */
    public function listComments(EnterpriseScope $scope, string $publicId): array;

    /**
     * @return list<TaskHistory>
     */
    public function history(EnterpriseScope $scope, string $publicId): array;

    public function statistics(EnterpriseScope $scope): TaskStatistics;

    /**
     * @return list<HumanTaskReference>
     */
    public function inbox(EnterpriseScope $scope, string $inboxType, ?string $membershipId = null, int $limit = 50): array;
}
