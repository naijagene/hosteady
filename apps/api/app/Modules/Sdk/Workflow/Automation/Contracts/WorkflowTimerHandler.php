<?php

namespace App\Modules\Sdk\Workflow\Automation\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationResult;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTimerReference;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;

interface WorkflowTimerHandler
{
    /**
     * @param  array<string, mixed>  $node
     */
    public function createFromWaitNode(
        EnterpriseScope $scope,
        string $workflowInstancePublicId,
        array $node,
        WorkflowExecutionContext $context,
    ): WorkflowTimerReference;

    public function executeDueTimer(string $timerPublicId): WorkflowAutomationResult;

    public function cancelTimer(EnterpriseScope $scope, string $timerPublicId): WorkflowTimerReference;
}
