<?php

namespace App\Modules\Sdk\Workflow\Runtime\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionResult;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionStatistics;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowInstanceReference;

interface WorkflowExecutionHandler
{
    public function supports(string $nodeType): bool;

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $variables
     */
    public function execute(
        string $nodeType,
        array $node,
        WorkflowExecutionContext $context,
        array $variables,
    ): \App\Modules\Sdk\Workflow\Runtime\Data\WorkflowActionResult;
}
