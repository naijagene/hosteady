<?php

namespace App\Modules\Sdk\Workflow\Human\Contracts;

use App\Modules\Sdk\Workflow\Human\Data\TaskAssignment;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Support\Tenant\TenantContext;

interface TaskAssignmentStrategy
{
    public function supports(string $assignmentType): bool;

    /**
     * @param  array<string, mixed>  $nodeConfig
     * @param  array<string, mixed>  $variables
     */
    public function resolve(
        TenantContext $context,
        string $taskPublicId,
        string $assignmentType,
        array $nodeConfig,
        array $variables,
        WorkflowExecutionContext $executionContext,
    ): TaskAssignment;
}
