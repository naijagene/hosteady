<?php

namespace App\Modules\Sdk\Workflow\Runtime\Contracts;

interface WorkflowActionHandler
{
    /**
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $variables
     */
    public function handle(
        string $actionType,
        array $action,
        array $variables,
    ): \App\Modules\Sdk\Workflow\Runtime\Data\WorkflowActionResult;
}
